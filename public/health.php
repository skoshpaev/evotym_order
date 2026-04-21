<?php

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'app' => 'order',
    'sapi' => PHP_SAPI,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
