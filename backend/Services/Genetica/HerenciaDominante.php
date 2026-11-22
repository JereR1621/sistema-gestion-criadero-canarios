<?php

declare(strict_types=1);

namespace TuCriadero\Services\Genetica;

use InvalidArgumentException;

/**
 * Regla de herencia dominante autosómica (SRS-RF038, SUP-004, docs/backend.md §5).
 *
 * Por SUP-004, todo progenitor que manifiesta un factor dominante (por ejemplo, INTENSO
 * o BLANCO DOMINANTE) se modela con una sola dosis (Aa), porque la variedad no indica
 * si tiene una o dos. Un factor dominante no admite portadores (DAT-004).
 */
final class HerenciaDominante implements ReglaHerencia
{
    public function tipoHerencia(): string
    {
        return 'dominante';
    }

    /**
     * Atajo directo cuando solo se conoce si cada progenitor manifiesta el factor dominante.
     *
     * @return array{
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    public function calcularDesdeProgenitores(bool $machoManifiesta, bool $hembraManifiesta): array
    {
        return $this->calcular(
            $machoManifiesta ? 1 : 0,
            false,
            $hembraManifiesta ? 1 : 0,
            false
        );
    }

    public function calcular(
        int $dosisMacho,
        bool $machoPortador,
        int $dosisHembra,
        bool $hembraPortadora,
    ): array {
        if ($machoPortador || $hembraPortadora) {
            throw new InvalidArgumentException('Un factor dominante no puede tener portadores (DAT-004).');
        }

        if (($dosisMacho !== 0 && $dosisMacho !== 1) || ($dosisHembra !== 0 && $dosisHembra !== 1)) {
            throw new InvalidArgumentException(
                'Un factor dominante admite solo 0 (normal) o 1 (una sola dosis según SUP-004).'
            );
        }

        $gametosMacho = $dosisMacho === 1 ? [0, 1] : [0, 0];
        $gametosHembra = $dosisHembra === 1 ? [0, 1] : [0, 0];

        $conteos = [0 => 0, 1 => 0];
        foreach ($gametosMacho as $aleloMacho) {
            foreach ($gametosHembra as $aleloHembra) {
                $manifiesta = ($aleloMacho + $aleloHembra) > 0 ? 1 : 0;
                $conteos[$manifiesta]++;
            }
        }

        $proporciones = [];
        foreach ($conteos as $dosisFenotipo => $cantidad) {
            if ($cantidad === 0) {
                continue;
            }

            $proporciones[] = [
                'manifiesta' => $dosisFenotipo === 1,
                'dosis' => $dosisFenotipo,
                'portador' => false,
                'porcentaje' => ($cantidad / 4) * 100.0,
            ];
        }

        // Herencia autosómica: machos y hembras tienen las mismas proporciones.
        return [
            'machos' => $proporciones,
            'hembras' => $proporciones,
        ];
    }
}
