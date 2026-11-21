<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Comprueba los seeds del catálogo (T12 y T03) contra el nomenclador y las reglas de docs/backend.md §3 y §6.
 * Lee los archivos SQL: no necesita base de datos.
 */
final class CatalogoTest extends TestCase
{
    private const SEEDS = __DIR__ . '/../database/seeds/';

    /** Nombre del nomenclador → factores. Las frases se leen antes que las palabras sueltas. */
    private const FRASES = [
        'BLANCO DOMINANTE' => ['BLANCO DOMINANTE'],
        'ALBINO DOMINANTE' => ['INO', 'BLANCO DOMINANTE'],
        'PICO AMARILLO' => ['PICO AMARILLO'],
        'JASPE SD' => ['JASPE'],
        'JASPE DD' => ['JASPE'],   // dosis 2: ver DOBLE_DOSIS
        'ALA BLANCA' => [],   // rasgo, no factor
        'ALAS GRISES' => [],  // rasgo, no factor
    ];

    private const PALABRAS = [
        'AMARILLO' => [], 'NEGRO' => [], 'NEVADO' => [], 'MACHO' => [], 'HEMBRA' => [],
        'ROJO' => ['ROJO'], 'BLANCO' => ['BLANCO RECESIVO'], 'INTENSO' => ['INTENSO'], 'MOSAICO' => ['MOSAICO'],
        'MARFIL' => ['MARFIL'], 'BRUNO' => ['BRUNO'], 'ÁGATA' => ['ÁGATA'], 'ISABEL' => ['BRUNO', 'ÁGATA'],
        'LUTINO' => ['INO'], 'RUBINO' => ['INO', 'ROJO'], 'ALBINO' => ['INO', 'BLANCO RECESIVO'],
        'FEO' => ['INO'],     // phaeo: ino sobre un melánico
        'OPALO' => ['ÓPALO'], 'PASTEL' => ['PASTEL'], 'SATINÉ' => ['SATINÉ'], 'TOPACIO' => ['TOPACIO'],
        'ÓNIX' => ['ÓNIX'], 'EUMO' => ['EUMO'], 'COBALTO' => ['COBALTO'], 'MOGNO' => ['MOGNO'],
        'PERLA' => ['PERLA'], 'ÚRUCUM' => ['URUCUM'],
    ];

    private const MELANICOS = ['NEGRO', 'BRUNO', 'ÁGATA', 'ISABEL', 'FEO'];

    /** Frase que lleva su factor con dosis 2 (codominante, backend.md §5). */
    private const DOBLE_DOSIS = ['JASPE DD' => 'JASPE'];

    /** @var array<string, array{nombre: string, sexo: ?string, tipo: string, rasgo: ?string}> */
    private static array $variedades;
    /** @var array<int, string> id → nombre */
    private static array $factores;
    /** @var array<string, array<int, int>> código → [id de factor => dosis] */
    private static array $composicion;

    public static function setUpBeforeClass(): void
    {
        preg_match_all("/\('(D\.\d{3})', '([^']*)', (NULL|'\w+'), '(\w+)', (NULL|'\w+')\)/", self::leer('01_variedades.sql'), $v, PREG_SET_ORDER);
        foreach ($v as [, $codigo, $nombre, $sexo, $tipo, $rasgo]) {
            self::$variedades[$codigo] = ['nombre' => $nombre, 'sexo' => self::valor($sexo), 'tipo' => $tipo, 'rasgo' => self::valor($rasgo)];
        }
        preg_match_all("/\((\d+), '([^']+)', '\w+'\)/", self::leer('02_factores.sql'), $f, PREG_SET_ORDER);
        self::$factores = array_column($f, 2, 1);
        preg_match_all("/\('(D\.\d{3})', (\d+)(?:, (\d))?\)/", self::leer('03_composicion.sql'), $c, PREG_SET_ORDER);
        self::$composicion = [];
        foreach ($c as $fila) {
            [, $codigo, $id] = $fila;
            self::assertArrayNotHasKey((int) $id, self::$composicion[$codigo] ?? [], "{$codigo} repite el factor {$id}.");
            self::$composicion[$codigo][(int) $id] = (int) ($fila[3] ?? 1);
        }
    }

    public function testLasVariedadesSonLasDelNomenclador(): void
    {
        $filas = array_slice(file(__DIR__ . '/../../docs/catalogo/nomenclador-color-2026.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 1);
        $csv = [];
        foreach ($filas as $fila) {
            [$codigo, $nombre] = explode(';', $fila, 2);
            $csv[$codigo] = trim($nombre);
        }

        self::assertCount(880, self::$variedades);
        self::assertSame($csv, array_combine(array_keys(self::$variedades), array_column(self::$variedades, 'nombre')));
    }

    public function testElSexoElTipoYElRasgoSalenDelNombre(): void
    {
        foreach (self::$variedades as $codigo => $v) {
            $palabras = explode(' ', $v['nombre']);
            $sexo = in_array('MACHO', $palabras, true) ? 'macho' : (in_array('HEMBRA', $palabras, true) ? 'hembra' : null);
            $tipo = array_intersect(self::MELANICOS, $palabras) !== [] ? 'melanico' : 'lipocromico';
            $rasgo = str_contains($v['nombre'], 'ALA BLANCA') ? 'ala_blanca' : (str_contains($v['nombre'], 'ALAS GRISES') ? 'alas_grises' : null);

            self::assertSame([$sexo, $tipo, $rasgo], [$v['sexo'], $v['tipo'], $v['rasgo']], "{$codigo} {$v['nombre']}");
        }
    }

    public function testHay21FactoresSinRasgosNiDosisComoFactor(): void
    {
        self::assertCount(21, self::$factores);
        self::assertSame([], array_intersect(self::$factores, ['LIPOCROMO', 'ALA BLANCA', 'ALAS GRISES', 'FEO', 'JASPE DOBLE DOSIS']));
        self::assertStringContainsString("(18, 'JASPE', 'codominante')", self::leer('02_factores.sql'));
    }

    public function testLaComposicionNoUsaCodigosOFactoresInexistentes(): void
    {
        foreach (self::$composicion as $codigo => $factores) {
            self::assertArrayHasKey($codigo, self::$variedades);
            self::assertSame([], array_diff(array_keys($factores), array_keys(self::$factores)), "{$codigo} usa un factor que no existe.");
        }
    }

    public function testLaComposicionCorrespondeAlNombre(): void
    {
        $porNombre = array_flip(self::$factores);
        foreach (self::$variedades as $codigo => $v) {
            $esperados = [];
            foreach (self::descomponer($v['nombre']) as $factor => $dosis) {
                $esperados[$porNombre[$factor]] = $dosis;
            }
            $cargados = self::$composicion[$codigo] ?? [];   // sin filas = forma normal (por ejemplo, D.004 AMARILLO NEVADO)
            ksort($esperados);
            ksort($cargados);

            self::assertSame($esperados, $cargados, "{$codigo} {$v['nombre']}");
        }
    }

    public function testNingunaCombinacionDeFactoresYDosisSeRepiteParaElMismoSexoTipoYRasgo(): void
    {
        $vistas = [];
        foreach (self::$variedades as $codigo => $v) {
            $factores = self::$composicion[$codigo] ?? [];
            ksort($factores);
            $clave = implode('|', [$v['sexo'], $v['tipo'], $v['rasgo'], http_build_query($factores)]);

            self::assertArrayNotHasKey($clave, $vistas, "{$codigo} repite la combinación de " . ($vistas[$clave] ?? ''));
            $vistas[$clave] = $codigo;
        }
    }

    /** @return array<string, int> nombre de factor → dosis */
    private static function descomponer(string $nombre): array
    {
        $texto = " {$nombre} ";
        $factores = [];
        foreach (self::FRASES as $frase => $deLaFrase) {
            if (str_contains($texto, " {$frase} ")) {
                $factores += array_fill_keys($deLaFrase, 1);
                if (isset(self::DOBLE_DOSIS[$frase])) {
                    $factores[self::DOBLE_DOSIS[$frase]] = 2;
                }
                $texto = str_replace(" {$frase} ", ' ', $texto);
            }
        }
        foreach (array_filter(explode(' ', $texto)) as $palabra) {
            self::assertArrayHasKey($palabra, self::PALABRAS, "Palabra sin regla en «{$nombre}».");
            $factores += array_fill_keys(self::PALABRAS[$palabra], 1);
        }

        return $factores;
    }

    private static function leer(string $archivo): string
    {
        return (string) file_get_contents(self::SEEDS . $archivo);
    }

    private static function valor(string $sql): ?string
    {
        return $sql === 'NULL' ? null : trim($sql, "'");
    }
}
