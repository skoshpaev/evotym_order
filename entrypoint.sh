#!/bin/sh
set -eu

PROJECT_DIR="/workspace/order"
REPO_SSH_URL="git@github.com:skoshpaev/evotym_order.git"
REPO_HTTPS_URL="https://github.com/skoshpaev/evotym_order.git"
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

    clone_url="$REPO_SSH_URL"
    if [ -n "${GITHUB_TOKEN:-}" ]; then
        clone_url="https://x-access-token:${GITHUB_TOKEN}@github.com/skoshpaev/evotym_order.git"
        log "Cloning repository with token authentication"
    else
        log "Cloning repository over SSH"
    fi

    git clone "$clone_url" "$tmp_dir"
    git -C "$tmp_dir" remote set-url origin "$REPO_SSH_URL" >/dev/null 2>&1 || true
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
        git remote set-url origin "$REPO_SSH_URL" >/dev/null 2>&1 || true
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
