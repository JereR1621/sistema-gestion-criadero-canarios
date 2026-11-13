<?php

declare(strict_types=1);

// Router para el servidor integrado de PHP; Apache usa docker/apache.conf.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (is_file($archivo)) {
        return false;
    }
}

require __DIR__ . '/index.php';
