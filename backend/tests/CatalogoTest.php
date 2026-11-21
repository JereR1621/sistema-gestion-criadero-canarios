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
        'JASPE DD' => ['JASPE DOBLE DOSIS'],
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

    /** @var array<string, array{nombre: string, sexo: ?string, tipo: string, rasgo: ?string}> */
    private static array $variedades;
    /** @var array<int, string> id → nombre */
    private static array $factores;
    /** @var array<string, list<int>> código → ids de factor */
    private static array $composicion;

    public static function setUpBeforeClass(): void
    {
        preg_match_all("/\('(D\.\d{3})', '([^']*)', (NULL|'\w+'), '(\w+)', (NULL|'\w+')\)/", self::leer('01_variedades.sql'), $v, PREG_SET_ORDER);
        foreach ($v as [, $codigo, $nombre, $sexo, $tipo, $rasgo]) {
            self::$variedades[$codigo] = ['nombre' => $nombre, 'sexo' => self::valor($sexo), 'tipo' => $tipo, 'rasgo' => self::valor($rasgo)];
        }
        preg_match_all("/\((\d+), '([^']+)', '\w+'\)/", self::leer('02_factores.sql'), $f, PREG_SET_ORDER);
        self::$factores = array_column($f, 2, 1);
        preg_match_all("/\('(D\.\d{3})', (\d+)\)/", self::leer('03_composicion.sql'), $c, PREG_SET_ORDER);
        self::$composicion = [];
        foreach ($c as [, $codigo, $id]) {
            self::$composicion[$codigo][] = (int) $id;
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

    public function testHay22FactoresSinRasgosComoFactor(): void
    {
        self::assertCount(22, self::$factores);
        self::assertSame([], array_intersect(self::$factores, ['LIPOCROMO', 'ALA BLANCA', 'ALAS GRISES', 'FEO']));
    }

    public function testLaComposicionNoRepiteFilasNiUsaCodigosOFactoresInexistentes(): void
    {
        foreach (self::$composicion as $codigo => $ids) {
            self::assertArrayHasKey($codigo, self::$variedades);
            self::assertSame(array_values(array_unique($ids)), $ids, "{$codigo} repite un factor.");
            self::assertSame([], array_diff($ids, array_keys(self::$factores)), "{$codigo} usa un factor que no existe.");
        }
    }

    public function testLaComposicionCorrespondeAlNombre(): void
    {
        $porNombre = array_flip(self::$factores);
        foreach (self::$variedades as $codigo => $v) {
            $esperados = array_map(fn (string $f): int => $porNombre[$f], self::descomponer($v['nombre']));
            $cargados = self::$composicion[$codigo] ?? [];   // sin filas = forma normal (por ejemplo, D.004 AMARILLO NEVADO)
            sort($esperados);
            sort($cargados);

            self::assertSame($esperados, $cargados, "{$codigo} {$v['nombre']}");
        }
    }

    public function testNingunaCombinacionSeRepiteParaElMismoSexoTipoYRasgo(): void
    {
        $vistas = [];
        foreach (self::$variedades as $codigo => $v) {
            $ids = self::$composicion[$codigo] ?? [];
            sort($ids);
            $clave = implode('|', [$v['sexo'], $v['tipo'], $v['rasgo'], implode(',', $ids)]);

            self::assertArrayNotHasKey($clave, $vistas, "{$codigo} repite la combinación de " . ($vistas[$clave] ?? ''));
            $vistas[$clave] = $codigo;
        }
    }

    /** @return list<string> nombres de factor, sin repetir */
    private static function descomponer(string $nombre): array
    {
        $texto = " {$nombre} ";
        $factores = [];
        foreach (self::FRASES as $frase => $deLaFrase) {
            if (str_contains($texto, " {$frase} ")) {
                array_push($factores, ...$deLaFrase);
                $texto = str_replace(" {$frase} ", ' ', $texto);
            }
        }
        foreach (array_filter(explode(' ', $texto)) as $palabra) {
            self::assertArrayHasKey($palabra, self::PALABRAS, "Palabra sin regla en «{$nombre}».");
            array_push($factores, ...self::PALABRAS[$palabra]);
        }

        return array_values(array_unique($factores));
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
