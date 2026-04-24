#!/bin/sh
set -eu

PROJECT_DIR="/workspace/order"
REPO_NAME="evotym_order"
SYMFONY_SKELETON="symfony/skeleton:^6.4"
TMP_DIRS=""

log() {
    printf '[entrypoint] %s\n' "$*"
}

cleanup() {
    for dir in $TMP_DIRS; do
        if [ -n "$dir" ] && [ -d "$dir" ]; then
            rm -rf "$dir"
        fi
    done
}

track_tmp_dir() {
    TMP_DIRS="$TMP_DIRS $1"
}

extract_github_owner() {
    remote_url="$1"

    printf '%s\n' "$remote_url" | sed -n 's#.*github.com[:/]\([^/]*\)/.*#\1#p'
}

detect_github_owner() {
    if [ -n "${GITHUB_OWNER:-}" ]; then
        printf '%s\n' "$GITHUB_OWNER"
        return 0
    fi

    if [ -d "$PROJECT_DIR/.git" ]; then
        owner="$(extract_github_owner "$(git -C "$PROJECT_DIR" remote get-url origin 2>/dev/null || true)")"

        if [ -n "$owner" ]; then
            printf '%s\n' "$owner"
            return 0
        fi
    fi

    if [ -n "${PROJECT_REPO_HTTPS_URL:-}" ]; then
        owner="$(extract_github_owner "$PROJECT_REPO_HTTPS_URL")"

        if [ -n "$owner" ]; then
            printf '%s\n' "$owner"
            return 0
        fi
    fi

    if [ -n "${PROJECT_REPO_SSH_URL:-}" ]; then
        owner="$(extract_github_owner "$PROJECT_REPO_SSH_URL")"

        if [ -n "$owner" ]; then
            printf '%s\n' "$owner"
            return 0
        fi
    fi

    return 1
}

get_https_repo_url() {
    if [ -n "${PROJECT_REPO_HTTPS_URL:-}" ]; then
        printf '%s\n' "$PROJECT_REPO_HTTPS_URL"
        return 0
    fi

    owner="$(detect_github_owner || true)"

    if [ -z "$owner" ]; then
        return 1
    fi

    printf 'https://github.com/%s/%s.git\n' "$owner" "$REPO_NAME"
}

get_ssh_repo_url() {
    if [ -n "${PROJECT_REPO_SSH_URL:-}" ]; then
        printf '%s\n' "$PROJECT_REPO_SSH_URL"
        return 0
    fi

    owner="$(detect_github_owner || true)"

    if [ -z "$owner" ]; then
        return 1
    fi

    printf 'git@github.com:%s/%s.git\n' "$owner" "$REPO_NAME"
}

is_valid_symfony_project() {
    [ -f "$PROJECT_DIR/composer.json" ] && [ -f "$PROJECT_DIR/bin/console" ]
}

has_composer_project() {
    [ -f "$PROJECT_DIR/composer.json" ]
}

has_vendor() {
    [ -f "$PROJECT_DIR/vendor/autoload.php" ]
}

can_bootstrap_in_place() {
    if find "$PROJECT_DIR" -mindepth 1 -maxdepth 1 \
        ! -name '.git' \
        ! -name '.gitignore' \
        ! -name '.dockerignore' \
        ! -name 'Dockerfile' \
        ! -name 'docker-compose.yml' \
        ! -name 'entrypoint.sh' \
        ! -name 'docker' \
        ! -name '.DS_Store' \
        -print -quit | grep -q .; then
        return 1
    fi

    return 0
}

clone_repository_if_needed() {
    if is_valid_symfony_project || has_composer_project || [ -d "$PROJECT_DIR/.git" ]; then
        return 0
    fi

    tmp_dir="$(mktemp -d)"
    track_tmp_dir "$tmp_dir"

    repo_https_url="$(get_https_repo_url || true)"

    if [ -z "$repo_https_url" ]; then
        log "Repository URL is not configured. Set GITHUB_OWNER or PROJECT_REPO_HTTPS_URL."
        exit 1
    fi

    clone_url="$repo_https_url"
    if [ -n "${GITHUB_TOKEN:-}" ]; then
        clone_url="https://x-access-token:${GITHUB_TOKEN}@${repo_https_url#https://}"
        log "Cloning repository with token authentication"
    else
        log "Cloning repository over HTTPS"
    fi

    git clone "$clone_url" "$tmp_dir"
    repo_ssh_url="$(get_ssh_repo_url || true)"
    if [ -n "$repo_ssh_url" ]; then
        git -C "$tmp_dir" remote set-url origin "$repo_ssh_url" >/dev/null 2>&1 || true
    fi
    cp -an "$tmp_dir"/. "$PROJECT_DIR"/
}

bootstrap_symfony_skeleton_if_needed() {
    if is_valid_symfony_project; then
        return 0
    fi

    if ! can_bootstrap_in_place; then
        log "Project is not a valid Symfony app, but non-bootstrap files are present. Skipping skeleton generation."
        return 0
    fi

    tmp_dir="$(mktemp -d)"
    track_tmp_dir "$tmp_dir"

    log "Bootstrapping Symfony 6.4 skeleton"
    composer create-project "$SYMFONY_SKELETON" "$tmp_dir" --no-interaction
    mkdir -p "$tmp_dir/migrations" "$tmp_dir/templates" "$tmp_dir/var/cache" "$tmp_dir/var/log"
    touch "$tmp_dir/migrations/.gitkeep" "$tmp_dir/templates/.gitkeep"
    cp -an "$tmp_dir"/. "$PROJECT_DIR"/
}

install_dependencies_if_needed() {
    if has_composer_project && ! has_vendor; then
        log "Installing Composer dependencies"
        composer install --no-interaction --prefer-dist
    fi
}

ensure_runtime_directories() {
    mkdir -p "$PROJECT_DIR/var/cache" "$PROJECT_DIR/var/log"

    if [ ! -d "$PROJECT_DIR/migrations" ]; then
        mkdir -p "$PROJECT_DIR/migrations"
        touch "$PROJECT_DIR/migrations/.gitkeep"
    fi

    if [ ! -d "$PROJECT_DIR/templates" ]; then
        mkdir -p "$PROJECT_DIR/templates"
        touch "$PROJECT_DIR/templates/.gitkeep"
    fi

    if [ ! -d "$PROJECT_DIR/docker/nginx" ]; then
        mkdir -p "$PROJECT_DIR/docker/nginx"
    fi

    chmod -R ug+rwX "$PROJECT_DIR/var" || true
}

verify_symfony_project() {
    if is_valid_symfony_project; then
        log "Running Symfony health check"
        php "$PROJECT_DIR/bin/console" about
    fi
}

main() {
    trap cleanup EXIT INT TERM

    export COMPOSER_ALLOW_SUPERUSER=1
    mkdir -p "$PROJECT_DIR" "${COMPOSER_CACHE_DIR:-/tmp/composer-cache}"
    cd "$PROJECT_DIR"

    clone_repository_if_needed

    if [ -d "$PROJECT_DIR/.git" ]; then
        git config --global --add safe.directory "$PROJECT_DIR" >/dev/null 2>&1 || true
        repo_ssh_url="$(get_ssh_repo_url || true)"
        if [ -n "$repo_ssh_url" ]; then
            git remote set-url origin "$repo_ssh_url" >/dev/null 2>&1 || true
        fi
    fi

    bootstrap_symfony_skeleton_if_needed
    install_dependencies_if_needed
    ensure_runtime_directories
    verify_symfony_project

    if [ "$#" -eq 0 ]; then
        set -- sleep infinity
    fi

    exec "$@"
}

main "$@"
