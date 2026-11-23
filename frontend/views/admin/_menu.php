<?php
/**
 * Elementos del menú de administración (aislados en `frontend/views/admin/` para que
 * ninguna vista de criador contenga referencias a rutas del catálogo administrativo, RF030).
 *
 * @return list<array{0: string, 1: string, 2: string, 3: string}>
 */
$rutaOpcional ??= static function (string $nombreRuta): ?string {
    try {
        return ruta($nombreRuta);
    } catch (\InvalidArgumentException) {
        return null;
    }
};

return array_values(array_filter(array_map(
    static function (array $item) use ($rutaOpcional): ?array {
        $u = $rutaOpcional($item[0]);
        return $u !== null ? [$item[0], $item[1], $item[2], $u] : null;
    },
    [
        ['admin.cuentas', 'Cuentas', 'lock'],
        ['admin.variedades', 'Variedades', 'file'],
        ['admin.factores', 'Factores', 'dna'],
    ]
)));
