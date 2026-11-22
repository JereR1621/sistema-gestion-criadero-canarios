<?php

declare(strict_types=1);

namespace TuCriadero\Services\Genetica;

/**
 * Contrato común para cada regla de herencia de la tabla de Mendel (docs/backend.md §5, RNF007).
 *
 * Cada regla calcula de forma exacta y determinista las proporciones esperadas por sexo
 * para un único factor genético, sin consultar la base de datos.
 */
interface ReglaHerencia
{
    /** Tipo de herencia que resuelve esta regla (ej. 'dominante', 'codominante'). */
    public function tipoHerencia(): string;

    /**
     * Calcula las proporciones esperadas para machos y hembras de un factor en un cruce.
     *
     * @param int  $dosisMacho      Copias manifestadas por el macho (0 = no manifiesta, 1 = dosis simple, 2 = doble dosis)
     * @param bool $machoPortador   Si el macho porta el factor sin manifestarlo
     * @param int  $dosisHembra     Copias manifestadas por la hembra (0 = no manifiesta, 1 = dosis simple, 2 = doble dosis)
     * @param bool $hembraPortadora Si la hembra porta el factor sin manifestarlo
     *
     * @return array{
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    public function calcular(
        int $dosisMacho,
        bool $machoPortador,
        int $dosisHembra,
        bool $hembraPortadora,
    ): array;
}
