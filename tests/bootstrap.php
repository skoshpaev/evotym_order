<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';
removeDirectory(dirname(__DIR__).'/var/cache/test');

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$_SERVER['TEST_DATABASE_URL'] = $_ENV['TEST_DATABASE_URL'] = sprintf(
    'sqlite:///%s/var/test.db',
    dirname(__DIR__),
);

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path.'/'.$item;

        if (is_dir($itemPath) && !is_link($itemPath)) {
            removeDirectory($itemPath);
            continue;
        }

        @unlink($itemPath);
    }

    @rmdir($path);
}
