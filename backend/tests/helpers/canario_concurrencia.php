<?php
declare(strict_types=1);

// Trabajador de integración de RF007. Solo CLI y base de pruebas; la barrera no existe en el producto.
require dirname(__DIR__, 2) . '/autoload.php';

use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\Rutas;
use TuCriadero\Services\CanarioService;

if (PHP_SAPI !== 'cli' || $argc !== 5) {
    exit(2);
}
Entorno::cargar(dirname(__DIR__, 3) . '/.env');
$base = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';
if ($base === '' || $base === Entorno::obtener('DB_NAME')) {
    throw new RuntimeException('La integración requiere una base de pruebas separada.');
}
$pdo = new class('mysql:host=' . Entorno::obtener('DB_HOST') . ';port=' . (Entorno::obtener('DB_PORT') ?? '3306')
    . ';dbname=' . $base . ';charset=utf8mb4', Entorno::obtener('DB_USER'), Entorno::obtener('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]) extends PDO {
        public function beginTransaction(): bool
        {
            fwrite(STDOUT, "LISTO\n");
            fflush(STDOUT);
            if (trim((string) fgets(STDIN)) !== 'CONTINUAR') {
                throw new RuntimeException('Se canceló la barrera de concurrencia.');
            }
            return parent::beginTransaction();
        }
    };
Conexion::usar($pdo);
Rutas::definir(Aplicacion::crearRouter());
$_SESSION = ['usuario_id' => (int) $argv[1], 'rol' => 'criador', 'criadero_id' => (int) $argv[2]];
$resultado = (new CanarioService())->actualizar((int) $argv[3], (int) $argv[2], [
    'anillo_socio' => 'S-77', 'anillo_anio' => '2024', 'anillo_correlativo' => '805', 'anillo_club' => 'Club Norte',
    'sexo' => 'macho', 'variedad_codigo' => '', 'padre_id' => $argv[4], 'madre_id' => '', 'procedencia' => '',
]);
echo json_encode($resultado, JSON_THROW_ON_ERROR);
