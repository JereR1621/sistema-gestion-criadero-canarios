<?php

declare(strict_types=1);

/**
 * Cargador mínimo de la aplicación. Composer añade un cargador equivalente
 * cuando vendor/ existe; este permite que la base arranque antes de instalarlo.
 */
spl_autoload_register(static function (string $clase): void {
    $prefijo = 'TuCriadero\\';

    if (!str_starts_with($clase, $prefijo)) {
        return;
    }

    $relativa = str_replace('\\', DIRECTORY_SEPARATOR, substr($clase, strlen($prefijo))) . '.php';
    $archivo = __DIR__ . DIRECTORY_SEPARATOR . $relativa;

    if (is_file($archivo)) {
        require $archivo;
    }
});

$autoloadComposer = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoloadComposer)) {
    require_once $autoloadComposer;
}

require_once __DIR__ . '/Support/helpers.php';
