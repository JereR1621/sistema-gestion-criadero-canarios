<?php

declare(strict_types=1);

namespace TuCriadero\Services\Genetica;

use InvalidArgumentException;

/**
 * Regla de herencia codominante autosómica por dosis (SRS-RF038, decisión del PM 21/11/2026, docs/backend.md §5).
 *
 * Modela factores como JASPE donde cada copia es visible en el fenotipo:
 * - 0 copias: sin mutación (normal)
 * - 1 copia: simple dosis (SD)
 * - 2 copias: doble dosis (DD)
 *
 * Se cruza como un carácter autosómico (cada padre pasa una copia), sin portadores ocultos (DAT-004).
 */
final class HerenciaCodominante implements ReglaHerencia
{
    public function tipoHerencia(): string
    {
        return 'codominante';
    }

    /**
     * Atajo directo por número de copias de cada progenitor (0 = normal, 1 = SD, 2 = DD).
     *
     * @return array{
     *     machos: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>,
     *     hembras: list<array{manifiesta: bool, dosis: int, portador: bool, porcentaje: float}>
     * }
     */
    public function calcularPorDosis(int $dosisMacho, int $dosisHembra): array
    {
        return $this->calcular($dosisMacho, false, $dosisHembra, false);
    }

    public function calcular(
        int $dosisMacho,
        bool $machoPortador,
        int $dosisHembra,
        bool $hembraPortadora,
    ): array {
        if ($machoPortador || $hembraPortadora) {
            throw new InvalidArgumentException('Un factor codominante no puede tener portadores (DAT-004).');
        }

        if ($dosisMacho < 0 || $dosisMacho > 2 || $dosisHembra < 0 || $dosisHembra > 2) {
            throw new InvalidArgumentException('La dosis de un factor codominante debe ser 0, 1 (SD) o 2 (DD).');
        }

        $gametosMacho = self::gametos($dosisMacho);
        $gametosHembra = self::gametos($dosisHembra);

        $conteos = [0 => 0, 1 => 0, 2 => 0];
        foreach ($gametosMacho as $aleloMacho) {
            foreach ($gametosHembra as $aleloHembra) {
                $dosisCria = $aleloMacho + $aleloHembra;
                $conteos[$dosisCria]++;
            }
        }

        $proporciones = [];
        foreach ($conteos as $dosisCria => $cantidad) {
            if ($cantidad === 0) {
                continue;
            }

            $proporciones[] = [
                'manifiesta' => $dosisCria > 0,
                'dosis' => $dosisCria,
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

    /** @return array{0: int, 1: int} */
    private static function gametos(int $dosis): array
    {
        return match ($dosis) {
            0 => [0, 0],
            1 => [0, 1],
            2 => [1, 1],
        };
    }
}
