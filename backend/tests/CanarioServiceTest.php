<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Throwable;
use TuCriadero\Controllers\CanarioController;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\Conexion;
use TuCriadero\Core\Entorno;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Router;
use TuCriadero\Core\Ruta;
use TuCriadero\Core\Rutas;
use TuCriadero\Services\CanarioService;

/**
 * Pruebas de SRS-RF007: Registro de canario (sexo y variedad pueden quedar sin determinar).
 */
final class CanarioServiceTest extends TestCase
{
    private PDO $pdo;
    private int $usuarioA;
    private int $criaderoA;
    private int $usuarioB;
    private int $criaderoB;
    private CanarioService $servicio;

    protected function setUp(): void
    {
        try {
            $this->pdo = self::baseDePruebas();
            $this->pdo->query('SELECT 1 FROM criadero LIMIT 1');
        } catch (Throwable) {
            self::markTestSkipped('Sin base de pruebas: levanta MySQL y corre php backend/database/migrar.php --seeds --pruebas.');
        }

        Conexion::usar($this->pdo);
        $this->pdo->beginTransaction();

        [$this->usuarioA, $this->criaderoA] = $this->crearCriadero('rf007.a@tucriadero.cl', 'S-77', 'Club Norte');
        [$this->usuarioB, $this->criaderoB] = $this->crearCriadero('rf007.b@tucriadero.cl', 'S-88', 'Club Sur');

        $_SESSION['usuario_id'] = $this->usuarioA;
        $_SESSION['rol'] = 'criador';
        $_SESSION['criadero_id'] = $this->criaderoA;
        $_SESSION['nombre'] = 'Criador A';
        $_POST = [];
        $_GET = [];

        Rutas::definir(Aplicacion::crearRouter());

        $this->servicio = new CanarioService();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        Conexion::usar(null);
        unset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['criadero_id'], $_SESSION['nombre']);
        $_POST = [];
        $_GET = [];
    }

    /**
     * Verifica las 6 rutas con nombre del contrato §6 para RF007 en el router
     * y que `/canarios/nuevo` esté registrada antes de `/canarios/{id}`.
     */
    public function testRutasDeCanariosRegistradasEnRouter(): void
    {
        $router = Aplicacion::crearRouter();

        $propiedad = new ReflectionProperty(Router::class, 'rutas');
        /** @var list<Ruta> $rutas */
        $rutas = $propiedad->getValue($router);

        $porNombre = [];
        $posiciones = [];
        foreach ($rutas as $indice => $ruta) {
            $porNombre[$ruta->nombre] = $ruta;
            $posiciones[$ruta->nombre] = $indice;
        }

        $esperadas = [
            'canarios' => ['GET', '/canarios', 'index'],
            'canarios.nuevo' => ['GET', '/canarios/nuevo', 'nuevo'],
            'canarios.guardar' => ['POST', '/canarios', 'guardar'],
            'canarios.ficha' => ['GET', '/canarios/{id}', 'ficha'],
            'canarios.editar' => ['GET', '/canarios/{id}/editar', 'editar'],
            'canarios.actualizar' => ['POST', '/canarios/{id}', 'actualizar'],
        ];

        foreach ($esperadas as $nombre => [$metodo, $patron, $accion]) {
            self::assertArrayHasKey($nombre, $porNombre, "Debe existir la ruta {$nombre}");
            self::assertSame($metodo, $porNombre[$nombre]->metodo);
            self::assertSame($patron, $porNombre[$nombre]->patron);
            self::assertSame(CanarioController::class, $porNombre[$nombre]->controlador);
            self::assertSame($accion, $porNombre[$nombre]->accion);
        }

        self::assertLessThan($posiciones['canarios.ficha'], $posiciones['canarios.nuevo']);

        self::assertSame('/canarios', Rutas::url('canarios'));
        self::assertSame('/canarios/nuevo', Rutas::url('canarios.nuevo'));
        self::assertSame('/canarios', Rutas::url('canarios.guardar'));
        self::assertSame('/canarios/42', Rutas::url('canarios.ficha', ['id' => 42]));
        self::assertSame('/canarios/42/editar', Rutas::url('canarios.editar', ['id' => 42]));
        self::assertSame('/canarios/42', Rutas::url('canarios.actualizar', ['id' => 42]));
    }

    /**
     * Criterio positivo (RF007 + RF033): registra un canario con las 4 partes del anillo,
     * sexo y variedad sin determinar, ubicación inicial «Sin ubicación» y auditoría;
     * luego permite completarlos al editar y calcula `factores_manifestados` desde el catálogo.
     */
    public function testRegistrarSinDeterminarSexoNiVariedadYCompletarDespues(): void
    {
        $registro = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '101',
            'anillo_club' => 'Club Norte',
            'sexo' => '',
            'variedad_codigo' => '',
            'fecha_anillado' => '2026-09-15',
            'fecha_nacimiento' => '2026-09-01',
            'padre_id' => '',
            'madre_id' => '',
            'procedencia' => '',
        ]);

        self::assertSame([], $registro['errores']);
        $canarioId = (int) $registro['canario_id'];

        $ficha = $this->servicio->obtenerFicha($canarioId, $this->criaderoA);
        self::assertNotNull($ficha);
        self::assertSame([
            'id',
            'anillo',
            'anillo_texto',
            'sexo',
            'variedad',
            'ubicacion_texto',
            'estado',
            'url',
            'fecha_anillado',
            'fecha_nacimiento',
            'procedencia',
            'padre',
            'madre',
            'hermanos',
            'medios_hermanos',
            'crias',
            'cruces',
            'factores_manifestados',
            'portadores',
            'baja',
            'arbol',
        ], array_keys($ficha));
        self::assertSame('sin_determinar', $ficha['sexo']);
        self::assertNull($ficha['variedad']);
        self::assertSame([], $ficha['factores_manifestados']);
        self::assertSame('Sin ubicación', $ficha['ubicacion_texto']);
        self::assertSame('S-77 · 2026 · 101 · Club Norte', $ficha['anillo_texto']);
        self::assertSame("/canarios/{$canarioId}", $ficha['url']);
        self::assertNull($ficha['padre']);
        self::assertNull($ficha['madre']);

        // Auditoría SEG-003 registrada en la creación
        $auditCrear = $this->pdo->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'crear' AND entidad = 'canario' AND entidad_id = ?");
        $auditCrear->execute([$canarioId]);
        self::assertSame(1, (int) $auditCrear->fetchColumn());

        // Completar sexo y variedad después (también con formato de datalist "código – nombre")
        $actualizacion = $this->servicio->actualizar($canarioId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '101',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'variedad_codigo' => 'D.049 – ROJO INTENSO',
            'fecha_anillado' => '2026-09-15',
            'fecha_nacimiento' => '2026-09-01',
            'padre_id' => '',
            'madre_id' => '',
            'procedencia' => '',
        ]);

        self::assertSame([], $actualizacion['errores']);
        $fichaActualizada = $this->servicio->obtenerFicha($canarioId, $this->criaderoA);
        self::assertNotNull($fichaActualizada);
        self::assertSame('macho', $fichaActualizada['sexo']);
        self::assertSame('D.049', $fichaActualizada['variedad']['codigo'] ?? null);
        self::assertNotEmpty($fichaActualizada['factores_manifestados']);
        foreach ($fichaActualizada['factores_manifestados'] as $factor) {
            self::assertArrayHasKey('nombre', $factor);
            self::assertArrayHasKey('tipo_herencia', $factor);
            self::assertArrayHasKey('dosis', $factor);
        }

        $auditActualizar = $this->pdo->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'actualizar' AND entidad = 'canario' AND entidad_id = ?");
        $auditActualizar->execute([$canarioId]);
        self::assertSame(1, (int) $auditActualizar->fetchColumn());
    }

    /**
     * Criterio: $por_defecto trae socio y club del perfil (RF004), listas vacías para variedades/portadores,
     * y en edición excluye al propio canario de posibles padres/madres.
     */
    public function testPrepararDatosFormularioEntregaContratoYValoresPorDefecto(): void
    {
        $padreId = $this->insertarCanario($this->criaderoA, 1, 2024, 'macho');
        $otroMachoId = $this->insertarCanario($this->criaderoA, 3, 2024, 'macho');
        $madreId = $this->insertarCanario($this->criaderoA, 2, 2024, 'hembra');
        // Canario de otro criadero no debe aparecer en posibles padres/madres
        $this->insertarCanario($this->criaderoB, 1, 2024, 'macho');

        $datos = $this->servicio->prepararDatosFormulario($this->usuarioA, $this->criaderoA);

        self::assertSame([], $datos['variedades']);
        self::assertSame([], $datos['factores_portables']);
        self::assertSame(['anillo_socio' => 'S-77', 'anillo_club' => 'Club Norte'], $datos['por_defecto']);
        self::assertSame([$padreId, $otroMachoId], array_column($datos['posibles_padres'], 'id'));
        self::assertSame([$madreId], array_column($datos['posibles_madres'], 'id'));

        $datosEdicion = $this->servicio->prepararDatosFormulario($this->usuarioA, $this->criaderoA, $padreId);
        self::assertSame([$otroMachoId], array_column($datosEdicion['posibles_padres'], 'id'));
    }

    /**
     * Criterio: listarActivos incluye activos y reservados, excluye bajas y otros criaderos, y entrega paginación.
     */
    public function testListarActivosIncluyeReservadosExcluyeBajasYPagina(): void
    {
        $activoId = $this->insertarCanario($this->criaderoA, 1, 2026, 'macho', null, null, 'activo');
        $reservadoId = $this->insertarCanario($this->criaderoA, 2, 2026, 'hembra', null, null, 'reservado');
        $bajaId = $this->insertarCanario($this->criaderoA, 3, 2026, 'macho', null, null, 'baja');
        $ajenoId = $this->insertarCanario($this->criaderoB, 4, 2026, 'macho', null, null, 'activo');

        $listado = $this->servicio->listarActivos($this->criaderoA, 1);

        self::assertSame(['pagina' => 1, 'total_paginas' => 1], $listado['paginacion']);
        $ids = array_column($listado['canarios'], 'id');
        self::assertContains($activoId, $ids);
        self::assertContains($reservadoId, $ids);
        self::assertNotContains($bajaId, $ids);
        self::assertNotContains($ajenoId, $ids);

        self::assertSame([
            'id',
            'anillo',
            'anillo_texto',
            'sexo',
            'variedad',
            'ubicacion_texto',
            'estado',
            'url',
        ], array_keys($listado['canarios'][0]));
    }

    /**
     * Criterio: un canario con padre y madre del criadero queda enlazado a ellos y arma su árbol.
     */
    public function testCanarioConPadreYMadreDelCriaderoQuedaEnlazado(): void
    {
        $padreId = $this->insertarCanario($this->criaderoA, 10, 2024, 'macho');
        $madreId = $this->insertarCanario($this->criaderoA, 11, 2025, 'hembra');

        $res = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '12',
            'anillo_club' => 'Club Norte',
            'sexo' => 'hembra',
            'variedad_codigo' => '',
            'padre_id' => (string) $padreId,
            'madre_id' => (string) $madreId,
        ]);

        self::assertSame([], $res['errores']);
        $ficha = $this->servicio->obtenerFicha((int) $res['canario_id'], $this->criaderoA);
        self::assertSame($padreId, $ficha['padre']['id'] ?? null);
        self::assertSame($madreId, $ficha['madre']['id'] ?? null);
        self::assertSame($padreId, $ficha['arbol']['padre']['id'] ?? null);
        self::assertSame($madreId, $ficha['arbol']['madre']['id'] ?? null);
    }

    /**
     * Criterio negativo: falta una parte del anillo (socio, año, correlativo o club); se informa sin guardar nada.
     */
    public function testNegativoFaltaUnaParteDelAnilloNoGuardaNada(): void
    {
        $antes = $this->contarCanarios();

        $base = [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '50',
            'anillo_club' => 'Club Norte',
            'sexo' => 'sin_determinar',
            'variedad_codigo' => '',
        ];

        foreach (['anillo_socio', 'anillo_anio', 'anillo_correlativo', 'anillo_club'] as $campo) {
            $incompleto = $base;
            $incompleto[$campo] = '';
            $res = $this->servicio->registrar($this->criaderoA, $incompleto);
            self::assertArrayHasKey($campo, $res['errores']);
        }

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: variedad inexistente, correlativo fuera de rango INT UNSIGNED, año/fechas inválidos
     * y valores no escalares se rechazan en sus campos correspondientes sin guardar ni emitir avisos.
     */
    public function testNegativoVariedadInexistenteCorrelativoDesbordadoYFechasInvalidas(): void
    {
        $antes = $this->contarCanarios();

        $resVariedad = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '51',
            'anillo_club' => 'Club Norte',
            'variedad_codigo' => 'Z.999',
        ]);
        self::assertArrayHasKey('variedad_codigo', $resVariedad['errores']);
        self::assertArrayNotHasKey('anillo_correlativo', $resVariedad['errores']);

        $resDesborde = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '26',
            'anillo_correlativo' => '4294967296',
            'anillo_club' => 'Club Norte',
            'sexo' => 'desconocido',
            'fecha_anillado' => '2026-02-30',
            'fecha_nacimiento' => '15-09-2026',
        ]);
        self::assertArrayHasKey('anillo_anio', $resDesborde['errores']);
        self::assertArrayHasKey('anillo_correlativo', $resDesborde['errores']);
        self::assertArrayHasKey('sexo', $resDesborde['errores']);
        self::assertArrayHasKey('fecha_anillado', $resDesborde['errores']);
        self::assertArrayHasKey('fecha_nacimiento', $resDesborde['errores']);

        $resArreglos = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => ['S-77'],
            'anillo_anio' => ['2026'],
            'anillo_correlativo' => ['52'],
            'anillo_club' => ['Club Norte'],
        ]);
        self::assertArrayHasKey('anillo_socio', $resArreglos['errores']);
        self::assertArrayHasKey('anillo_anio', $resArreglos['errores']);
        self::assertArrayHasKey('anillo_correlativo', $resArreglos['errores']);
        self::assertArrayHasKey('anillo_club', $resArreglos['errores']);

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: un padre que no es macho o una madre que no es hembra se rechaza.
     */
    public function testNegativoPadreNoMachoOMadreNoHembraSeRechaza(): void
    {
        $hembraId = $this->insertarCanario($this->criaderoA, 20, 2024, 'hembra');
        $machoId = $this->insertarCanario($this->criaderoA, 21, 2024, 'macho');
        $sinDetId = $this->insertarCanario($this->criaderoA, 22, 2024, 'sin_determinar');

        $antes = $this->contarCanarios();

        $res1 = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '23',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $hembraId,
            'madre_id' => (string) $machoId,
        ]);
        self::assertArrayHasKey('padre_id', $res1['errores']);
        self::assertArrayHasKey('madre_id', $res1['errores']);

        $res2 = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '24',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $sinDetId,
            'madre_id' => (string) $sinDetId,
        ]);
        self::assertArrayHasKey('padre_id', $res2['errores']);
        self::assertArrayHasKey('madre_id', $res2['errores']);

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: un canario con año de anillo anterior al de su padre o su madre se rechaza.
     */
    public function testNegativoAnioAnilloAnteriorAlDePadreOMadreSeRechaza(): void
    {
        $padre2025 = $this->insertarCanario($this->criaderoA, 30, 2025, 'macho');
        $madre2026 = $this->insertarCanario($this->criaderoA, 31, 2026, 'hembra');
        $antes = $this->contarCanarios();

        $resPadre = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2024',
            'anillo_correlativo' => '32',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $padre2025,
        ]);
        self::assertArrayHasKey('anillo_anio', $resPadre['errores']);

        $resMadre = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2025',
            'anillo_correlativo' => '33',
            'anillo_club' => 'Club Norte',
            'madre_id' => (string) $madre2026,
        ]);
        self::assertArrayHasKey('anillo_anio', $resMadre['errores']);

        self::assertSame($antes, $this->contarCanarios());
    }

    /**
     * Criterio negativo: indicar como padre o madre al propio canario o a uno de sus descendientes se rechaza.
     */
    public function testNegativoPadreOMadrePropioODescendienteSeRechaza(): void
    {
        $abueloId = $this->insertarCanario($this->criaderoA, 40, 2023, 'macho');
        $padreId = $this->insertarCanario($this->criaderoA, 41, 2024, 'macho', $abueloId);
        $hijoId = $this->insertarCanario($this->criaderoA, 42, 2025, 'macho', $padreId);

        // Sí mismo como padre
        $resPropio = $this->servicio->actualizar($abueloId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '40',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $abueloId,
        ]);
        self::assertArrayHasKey('padre_id', $resPropio['errores']);

        // Nieto (descendiente) como padre del abuelo
        $resDescendiente = $this->servicio->actualizar($abueloId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '40',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $hijoId,
        ]);
        self::assertArrayHasKey('padre_id', $resDescendiente['errores']);

        $abuelaId = $this->insertarCanario($this->criaderoA, 43, 2023, 'hembra');
        $madreId = $this->insertarCanario($this->criaderoA, 44, 2024, 'hembra', null, $abuelaId);
        $nietaId = $this->insertarCanario($this->criaderoA, 45, 2025, 'hembra', null, $madreId);

        // Sí misma como madre
        $resMadrePropia = $this->servicio->actualizar($abuelaId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '43',
            'anillo_club' => 'Club Norte',
            'sexo' => 'hembra',
            'madre_id' => (string) $abuelaId,
        ]);
        self::assertArrayHasKey('madre_id', $resMadrePropia['errores']);

        // Nieta (descendiente) como madre de la abuela
        $resMadreDescendiente = $this->servicio->actualizar($abuelaId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2023',
            'anillo_correlativo' => '43',
            'anillo_club' => 'Club Norte',
            'sexo' => 'hembra',
            'madre_id' => (string) $nietaId,
        ]);
        self::assertArrayHasKey('madre_id', $resMadreDescendiente['errores']);
    }

    /**
     * Regla de integridad (docs/backend.md §3): un canario comprado (con procedencia) se registra sin padres,
     * y a uno nacido de un cruce no se le pueden cambiar los padres al editar.
     */
    public function testNegativoCanarioCompradoONacidoDeCruceNoPermitePadresAMano(): void
    {
        $machoId = $this->insertarCanario($this->criaderoA, 60, 2024, 'macho');
        $hembraId = $this->insertarCanario($this->criaderoA, 61, 2024, 'hembra');
        $otroMachoId = $this->insertarCanario($this->criaderoA, 62, 2024, 'macho');
        $antes = $this->contarCanarios();

        // Canario comprado con padres a mano: rechazado
        $resCompradoConPadres = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'EX-10',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '63',
            'anillo_club' => 'Club Externo',
            'procedencia' => 'Criadero La Serena',
            'padre_id' => (string) $machoId,
            'madre_id' => (string) $hembraId,
        ]);
        self::assertArrayHasKey('padre_id', $resCompradoConPadres['errores']);
        self::assertArrayHasKey('madre_id', $resCompradoConPadres['errores']);
        self::assertSame($antes, $this->contarCanarios());

        // Canario nacido de un cruce: no permite cambiar padre/madre, sí otros datos
        $this->pdo->prepare('INSERT INTO jaula (criadero_id, numero) VALUES (?, 7)')->execute([$this->criaderoA]);
        $jaulaId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(
            "INSERT INTO cruce (criadero_id, macho_id, hembra_id, jaula_id, temporada, fecha_inicio)
             VALUES (?, ?, ?, ?, 2026, '2026-08-01')"
        )->execute([$this->criaderoA, $machoId, $hembraId, $jaulaId]);
        $cruceId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(
            "INSERT INTO canario
                (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, sexo, padre_id, madre_id, cruce_origen_id, estado)
             VALUES (?, 'S-77', 2026, 64, 'Club Norte', 'sin_determinar', ?, ?, ?, 'activo')"
        )->execute([$this->criaderoA, $machoId, $hembraId, $cruceId]);
        $criaId = (int) $this->pdo->lastInsertId();

        $resCambioPadres = $this->servicio->actualizar($criaId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '64',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $otroMachoId,
            'madre_id' => '',
        ]);
        self::assertArrayHasKey('padre_id', $resCambioPadres['errores']);
        self::assertArrayHasKey('madre_id', $resCambioPadres['errores']);

        $resMismosPadres = $this->servicio->actualizar($criaId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '64',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'padre_id' => (string) $machoId,
            'madre_id' => (string) $hembraId,
        ]);
        self::assertSame([], $resMismosPadres['errores']);
    }

    /**
     * Criterios negativos de aislamiento (RF003):
     * - Padre/madre de otro criadero en formulario se rechaza sin guardar.
     * - Listado activo solo devuelve datos del criadero de la sesión.
     * - Pedir ficha, edición o actualización de un canario de otro criadero responde 404.
     */
    public function testNegativoAislamientoEntreCriaderosY404(): void
    {
        $machoAjeno = $this->insertarCanario($this->criaderoB, 1, 2024, 'macho');
        $hembraAjena = $this->insertarCanario($this->criaderoB, 2, 2024, 'hembra');
        $antes = $this->contarCanarios();

        // Padre y madre de otro criadero rechazados
        $resPadresAjenos = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '99',
            'anillo_club' => 'Club Norte',
            'padre_id' => (string) $machoAjeno,
            'madre_id' => (string) $hembraAjena,
        ]);
        self::assertArrayHasKey('padre_id', $resPadresAjenos['errores']);
        self::assertArrayHasKey('madre_id', $resPadresAjenos['errores']);
        self::assertSame($antes, $this->contarCanarios());

        // Listado solo trae canarios del criadero A
        $propio = $this->insertarCanario($this->criaderoA, 5, 2026, 'hembra');
        $listado = $this->servicio->listarActivos($this->criaderoA, 1);
        $idsListados = array_column($listado['canarios'], 'id');
        self::assertContains($propio, $idsListados);
        self::assertNotContains($machoAjeno, $idsListados);

        // Controlador responde 404 en ficha, editar y actualizar de otro criadero
        $controlador = new CanarioController();
        foreach (['ficha', 'editar', 'actualizar'] as $accion) {
            try {
                $controlador->{$accion}((string) $machoAjeno);
                self::fail("Se esperaba HttpException 404 en CanarioController::{$accion}().");
            } catch (HttpException $error) {
                self::assertSame(404, $error->codigo());
            }
        }
    }

    /**
     * Control común de rol (RF003): el administrador no tiene criadero y recibe 403 en todas las rutas de canarios.
     */
    public function testNegativoAdministradorRecibe403EnTodasLasRutasDeCanarios(): void
    {
        $_SESSION['rol'] = 'admin';
        unset($_SESSION['criadero_id']);

        $controlador = new CanarioController();
        $invocaciones = [
            'index' => static fn (): mixed => $controlador->index(),
            'nuevo' => static fn (): mixed => $controlador->nuevo(),
            'guardar' => static fn (): mixed => $controlador->guardar(),
            'ficha' => static fn (): mixed => $controlador->ficha('1'),
            'editar' => static fn (): mixed => $controlador->editar('1'),
            'actualizar' => static fn (): mixed => $controlador->actualizar('1'),
        ];

        foreach ($invocaciones as $accion => $llamada) {
            try {
                $llamada();
                self::fail("Se esperaba HttpException 403 para admin en CanarioController::{$accion}().");
            } catch (HttpException $error) {
                self::assertSame(403, $error->codigo());
            }
        }
    }

    public function testEditarProgenitoresPreservaSexoYAnioDeSusHijos(): void
    {
        $padre = $this->insertarCanario($this->criaderoA, 801, 2024, 'macho');
        $madre = $this->insertarCanario($this->criaderoA, 802, 2024, 'hembra');
        $hijo = $this->insertarCanario($this->criaderoA, 803, 2025, 'macho', $padre, $madre);
        $auditoriaAntes = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();
        foreach ([[$padre, 801, 'macho', 'hembra'], [$madre, 802, 'hembra', 'macho']] as [$id, $n, $sexo, $incorrecto]) {
            $datos = $this->datosEdicion($n, $sexo);
            foreach ([$incorrecto, 'sin_determinar'] as $cambio) {
                $resultado = $this->servicio->actualizar($id, $this->criaderoA, array_replace($datos, ['sexo' => $cambio]));
                self::assertArrayHasKey('sexo', $resultado['errores']);
                self::assertSame($sexo, $this->servicio->obtenerFicha($id, $this->criaderoA)['sexo']);
            }
            $resultado = $this->servicio->actualizar($id, $this->criaderoA, array_replace($datos, ['anillo_anio' => '2026']));
            self::assertArrayHasKey('anillo_anio', $resultado['errores']);
            self::assertSame(2024, $this->servicio->obtenerFicha($id, $this->criaderoA)['anillo']['anio']);
        }
        self::assertSame($auditoriaAntes, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
        self::assertSame($padre, $this->servicio->obtenerFicha($hijo, $this->criaderoA)['padre']['id']);
        // Una corrección compatible sigue permitida.
        self::assertSame([], $this->servicio->actualizar($padre, $this->criaderoA,
            array_replace($this->datosEdicion(801, 'macho'), ['anillo_anio' => '2023']))['errores']);
    }

    /**
     * RF012 (#98): Variedad del catálogo con sexo solo se asigna a un canario del mismo sexo;
     * si no coincide, se rechaza en `variedad_codigo` sin guardar nada.
     */
    public function testValidacionVariedadSegunSexoEnRegistroYActualizacion(): void
    {
        $varMacho = $this->buscarCodigoVariedadPorSexo('macho');
        $varHembra = $this->buscarCodigoVariedadPorSexo('hembra');
        self::assertNotNull($varMacho, 'Debe existir al menos una variedad con sexo macho en el catálogo.');
        self::assertNotNull($varHembra, 'Debe existir al menos una variedad con sexo hembra en el catálogo.');

        $antesCanarios = $this->contarCanarios();
        $antesAuditoria = (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();

        // Negativo: canario macho con variedad exclusiva de hembra -> se rechaza sin guardar
        $rechazoRegistro = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '901',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'variedad_codigo' => $varHembra,
        ]);
        self::assertArrayHasKey('variedad_codigo', $rechazoRegistro['errores']);
        self::assertSame($antesCanarios, $this->contarCanarios());
        self::assertSame($antesAuditoria, (int) $this->pdo->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());

        // Positivo: canario macho con variedad compatible de macho -> guarda correctamente
        $okRegistro = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '901',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'variedad_codigo' => $varMacho,
        ]);
        self::assertSame([], $okRegistro['errores']);
        $canarioId = (int) $okRegistro['canario_id'];

        // Negativo al actualizar: cambiar variedad a una de hembra se rechaza sin alterar el registro
        $rechazoUpdate = $this->servicio->actualizar($canarioId, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '901',
            'anillo_club' => 'Club Norte',
            'sexo' => 'macho',
            'variedad_codigo' => $varHembra,
        ]);
        self::assertArrayHasKey('variedad_codigo', $rechazoUpdate['errores']);
        $ficha = $this->servicio->obtenerFicha($canarioId, $this->criaderoA);
        self::assertSame($varMacho, $ficha['variedad']['codigo'] ?? null);

        // Negativo: canario sin_determinar con variedad sexuada -> se rechaza sin guardar
        $rechazoSinDeterminar = $this->servicio->registrar($this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '902',
            'anillo_club' => 'Club Norte',
            'sexo' => 'sin_determinar',
            'variedad_codigo' => $varHembra,
        ]);
        self::assertArrayHasKey('variedad_codigo', $rechazoSinDeterminar['errores']);
        self::assertSame($antesCanarios + 1, $this->contarCanarios());

        // Negativo: actualizar un canario ID que pertenece a otro criadero se rechaza sin alterar nada
        $canarioAjeno = $this->insertarCanario($this->criaderoB, 903, 2026, 'hembra');
        $fichaAjenaAntes = $this->servicio->obtenerFicha($canarioAjeno, $this->criaderoB);
        $rechazoIdAjeno = $this->servicio->actualizar($canarioAjeno, $this->criaderoA, [
            'anillo_socio' => 'S-77',
            'anillo_anio' => '2026',
            'anillo_correlativo' => '903',
            'anillo_club' => 'Club Norte',
            'sexo' => 'hembra',
            'variedad_codigo' => $varHembra,
        ]);
        self::assertTrue($rechazoIdAjeno['no_encontrado'] ?? false);
        self::assertSame($fichaAjenaAntes, $this->servicio->obtenerFicha($canarioAjeno, $this->criaderoB));
    }

    private function buscarCodigoVariedadPorSexo(string $sexo): ?string
    {
        $stmt = $this->pdo->prepare('SELECT codigo FROM variedad WHERE sexo = ? LIMIT 1');
        $stmt->execute([$sexo]);
        $codigo = $stmt->fetchColumn();

        return is_string($codigo) && $codigo !== '' ? $codigo : null;
    }

    public function testRechazoNoGuardaNiCierraLaTransaccionDelLlamador(): void
    {
        $id = $this->insertarCanario($this->criaderoA, 804, 2024, 'macho');
        $antes = $this->servicio->obtenerFicha($id, $this->criaderoA);
        $resultado = $this->servicio->actualizar($id, $this->criaderoA,
            array_replace($this->datosEdicion(804, 'macho'), ['padre_id' => (string) $id]));
        self::assertArrayHasKey('padre_id', $resultado['errores']);
        self::assertTrue($this->pdo->inTransaction());
        self::assertSame($antes, $this->servicio->obtenerFicha($id, $this->criaderoA));
    }

    public function testDosSesionesNoPuedenGuardarUnCicloGenealogico(): void
    {
        $a = $this->insertarCanario($this->criaderoA, 805, 2024, 'macho');
        $b = $this->insertarCanario($this->criaderoA, 806, 2024, 'macho');
        // Las conexiones independientes necesitan fixtures confirmadas; solo son las creadas por este test.
        $this->pdo->commit();
        $proceso = null;
        $pipes = [];
        try {
            $proceso = proc_open([PHP_BINARY, __DIR__ . '/helpers/canario_concurrencia.php',
                (string) $this->usuarioA, (string) $this->criaderoA, (string) $a, (string) $b],
                [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
            self::assertIsResource($proceso);
            stream_set_timeout($pipes[1], 10);
            self::assertSame("LISTO\n", fgets($pipes[1]));
            // La otra sesión enlaza B -> A mientras la primera espera antes de abrir su transacción.
            $segundo = $this->servicio->actualizar($b, $this->criaderoA,
                array_replace($this->datosEdicion(806, 'macho'), ['padre_id' => (string) $a]));
            self::assertSame([], $segundo['errores']);
            fwrite($pipes[0], "CONTINUAR\n");
            fclose($pipes[0]);
            unset($pipes[0]);
            $primero = json_decode(stream_get_contents($pipes[1]), true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('padre_id', $primero['errores']);
            self::assertNull($this->servicio->obtenerFicha($a, $this->criaderoA)['padre']);
            self::assertSame($a, $this->servicio->obtenerFicha($b, $this->criaderoA)['padre']['id']);
        } finally {
            if (is_resource($proceso)) {
                proc_terminate($proceso);
            }
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            if (is_resource($proceso)) {
                proc_close($proceso);
            }
            $this->pdo->prepare('DELETE FROM auditoria WHERE usuario_id IN (?, ?)')->execute([$this->usuarioA, $this->usuarioB]);
            $this->pdo->prepare('DELETE FROM usuario WHERE id IN (?, ?)')->execute([$this->usuarioA, $this->usuarioB]);
        }
    }

    /** @return array<string,string> */
    private function datosEdicion(int $correlativo, string $sexo): array
    {
        return ['anillo_socio' => 'S-77', 'anillo_anio' => '2024', 'anillo_correlativo' => (string) $correlativo,
            'anillo_club' => 'Club Norte', 'sexo' => $sexo, 'variedad_codigo' => '', 'fecha_anillado' => '',
            'fecha_nacimiento' => '', 'padre_id' => '', 'madre_id' => '', 'procedencia' => ''];
    }

    /** @return array{int, int} */
    private function crearCriadero(string $correo, string $socio, string $club): array
    {
        $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena_hash, rol, estado, fecha_consentimiento, numero_socio, club)
             VALUES ('Criador RF007', ?, ?, 'criador', 'activa', CURRENT_TIMESTAMP, ?, ?)"
        )->execute([$correo, password_hash('ClaveValida2026', PASSWORD_DEFAULT), $socio, $club]);
        $usuarioId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO criadero (usuario_id) VALUES (?)')->execute([$usuarioId]);
        $criaderoId = (int) $this->pdo->lastInsertId();

        return [$usuarioId, $criaderoId];
    }

    private function insertarCanario(
        int $criaderoId,
        int $correlativo,
        int $anio,
        string $sexo,
        ?int $padreId = null,
        ?int $madreId = null,
        string $estado = 'activo',
    ): int {
        $this->pdo->prepare(
            "INSERT INTO canario (criadero_id, anillo_socio, anillo_anio, anillo_correlativo, anillo_club, sexo, padre_id, madre_id, estado)
             VALUES (?, 'S-77', ?, ?, 'Club Norte', ?, ?, ?, ?)"
        )->execute([$criaderoId, $anio, $correlativo, $sexo, $padreId, $madreId, $estado]);

        return (int) $this->pdo->lastInsertId();
    }

    private function contarCanarios(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM canario')->fetchColumn();
    }

    private static function baseDePruebas(): PDO
    {
        Entorno::cargar(dirname(__DIR__, 2) . '/.env');
        $anterior = $_ENV['DB_NAME'] ?? null;
        $_ENV['DB_NAME'] = Entorno::obtener('DB_NAME_PRUEBAS') ?? '';

        try {
            return Conexion::crearDesdeEntorno();
        } finally {
            if ($anterior === null) {
                unset($_ENV['DB_NAME']);
            } else {
                $_ENV['DB_NAME'] = $anterior;
            }
        }
    }
}
