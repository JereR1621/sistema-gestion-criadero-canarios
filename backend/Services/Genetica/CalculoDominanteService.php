<?php

declare(strict_types=1);

namespace TuCriadero\Services\Genetica;

use DomainException;
use TuCriadero\Services\CatalogoService;

/**
 * Resuelve el cálculo genético de factores dominantes y codominantes en un cruce (SRS-RF038),
 * obteniendo el tipo de herencia desde `CatalogoService` (RF031) y las dosis manifestadas desde
 * `FactoresManifestadosService` (RF033), sin consultar la base directamente ni guardar nada.
 */
final class CalculoDominanteService
{
    public function __construct(
        private readonly CatalogoService $catalogo = new CatalogoService(),
        private readonly FactoresManifestadosService $manifestados = new FactoresManifestadosService(),
        private readonly HerenciaDominante $reglaDominante = new HerenciaDominante(),
        private readonly HerenciaCodominante $reglaCodominante = new HerenciaCodominante(),
    ) {
    }

    /**
     * Calcula las proporciones esperadas por sexo para un factor dominante o codominante
     * a partir de los códigos de variedad de los progenitores.
     *
     * @return array{
     *     factor: array{id: int, nombre: string, tipo_herencia: string},
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    public function calcularPorVariedades(
        int $factorId,
        ?string $variedadMacho,
        ?string $variedadHembra,
    ): array {
        $factoresMacho = $this->manifestados->obtenerDesdeVariedad($variedadMacho);
        $factoresHembra = $this->manifestados->obtenerDesdeVariedad($variedadHembra);

        return $this->calcularConFactoresManifestados($factorId, $factoresMacho, $factoresHembra);
    }

    /**
     * Calcula las proporciones esperadas por sexo para un factor dominante o codominante
     * a partir de dos canarios registrados del criadero indicado (aislamiento RF003).
     * El `criadero_id` lo pasa el controlador desde la sesión.
     *
     * @return array{
     *     factor: array{id: int, nombre: string, tipo_herencia: string},
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    public function calcularPorCanarios(
        int $factorId,
        int $machoId,
        int $hembraId,
        int $criaderoId,
    ): array {
        $factoresMacho = $this->manifestados->obtenerPorCanarioId($machoId, $criaderoId);
        $factoresHembra = $this->manifestados->obtenerPorCanarioId($hembraId, $criaderoId);

        return $this->calcularConFactoresManifestados($factorId, $factoresMacho, $factoresHembra);
    }

    /**
     * @param list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}> $factoresMacho
     * @param list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}> $factoresHembra
     * @return array{
     *     factor: array{id: int, nombre: string, tipo_herencia: string},
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    private function calcularConFactoresManifestados(
        int $factorId,
        array $factoresMacho,
        array $factoresHembra,
    ): array {
        $factor = $this->catalogo->obtenerFactor($factorId);
        $regla = match ($factor['tipo_herencia']) {
            'dominante' => $this->reglaDominante,
            'codominante' => $this->reglaCodominante,
            default => throw new DomainException(sprintf(
                'El factor «%s» tiene herencia «%s»; esta regla solo calcula factores dominantes o codominantes.',
                $factor['nombre'],
                $factor['tipo_herencia']
            )),
        };

        $dosisMacho = self::extraerDosis($factoresMacho, $factorId);
        $dosisHembra = self::extraerDosis($factoresHembra, $factorId);

        $resultado = $regla->calcular($dosisMacho, false, $dosisHembra, false);

        return [
            'factor' => $factor,
            'machos' => $resultado['machos'],
            'hembras' => $resultado['hembras'],
        ];
    }

    /**
     * @param list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}> $factores
     */
    private static function extraerDosis(array $factores, int $factorId): int
    {
        foreach ($factores as $factor) {
            if ($factor['id'] === $factorId) {
                return $factor['dosis'];
            }
        }

        return 0;
    }
}
