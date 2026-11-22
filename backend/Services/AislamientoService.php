<?php

declare(strict_types=1);

namespace TuCriadero\Services;

use TuCriadero\Models\CanarioModel;
use TuCriadero\Models\CruceModel;
use TuCriadero\Models\JaulaModel;

/**
 * Validaciones transversales de aislamiento entre criaderos (SRS-RF003, DAT-004).
 *
 * Los servicios de canarios, jaulas, cruces, ubicaciones y bajas usan estas reglas
 * para rechazar referencias a registros de otro criadero sin guardar nada y sin revelar
 * datos del registro ajeno.
 */
final class AislamientoService
{
    public function __construct(
        private readonly CanarioModel $canarios = new CanarioModel(),
        private readonly JaulaModel $jaulas = new JaulaModel(),
        private readonly CruceModel $cruces = new CruceModel(),
    ) {
    }

    /**
     * Devuelve el canario si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     sexo: string,
     *     variedad_codigo: ?string,
     *     padre_id: ?int,
     *     madre_id: ?int,
     *     estado: string
     * }
     */
    public function exigirCanarioPropio(int $canarioId, int $criaderoId): array
    {
        return $this->canarios->obtenerPorIdO404($canarioId, $criaderoId);
    }

    /**
     * Devuelve la jaula si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{id: int, criadero_id: int, numero: int}
     */
    public function exigirJaulaPropia(int $jaulaId, int $criaderoId): array
    {
        return $this->jaulas->obtenerPorIdO404($jaulaId, $criaderoId);
    }

    /**
     * Devuelve el cruce si pertenece al criadero o responde 404 (RF003).
     *
     * @return array{
     *     id: int,
     *     criadero_id: int,
     *     macho_id: int,
     *     hembra_id: int,
     *     jaula_id: int,
     *     temporada: int,
     *     fecha_inicio: string,
     *     fecha_fin: ?string
     * }
     */
    public function exigirCrucePropio(int $cruceId, int $criaderoId): array
    {
        return $this->cruces->obtenerPorIdO404($cruceId, $criaderoId);
    }

    /**
     * Valida que un canario y una jaula (opcional si es jaula grande) pertenezcan al criadero (RF003, DAT-004).
     *
     * @return array<string, string> mapa de errores (vacío si ambos pertenecen al criadero)
     */
    public function validarCanarioYJaulaDelCriadero(int $canarioId, ?int $jaulaId, int $criaderoId): array
    {
        $errores = [];

        if (!$this->canarios->perteneceAlCriadero($canarioId, $criaderoId)) {
            $errores['canario_id'] = 'El canario no pertenece a tu criadero.';
        }

        if ($jaulaId !== null && !$this->jaulas->perteneceAlCriadero($jaulaId, $criaderoId)) {
            $errores['jaula_id'] = 'La jaula no pertenece a tu criadero.';
        }

        return $errores;
    }

    /**
     * Valida que un cruce pertenezca al criadero y siga activo (`fecha_fin` NULL) (RF003, RF022).
     *
     * @return array<string, string> mapa de errores (vacío si es válido)
     */
    public function validarCruceActivoDelCriadero(int $cruceId, int $criaderoId): array
    {
        $cruce = $this->cruces->buscarPorIdEnCriadero($cruceId, $criaderoId);
        if ($cruce === null || $cruce['fecha_fin'] !== null) {
            return [
                '_general' => 'El cruce ya está terminado o no pertenece a tu criadero.',
            ];
        }

        return [];
    }

    /**
     * Valida que el padre y/o la madre enviados en un formulario pertenezcan al criadero (RF003, DAT-004).
     *
     * @return array<string, string> mapa de errores (vacío si los padres indicados pertenecen al criadero)
     */
    public function validarPadresDelCriadero(?int $padreId, ?int $madreId, int $criaderoId): array
    {
        $errores = [];

        if ($padreId !== null && !$this->canarios->perteneceAlCriadero($padreId, $criaderoId)) {
            $errores['padre_id'] = 'El padre indicado no pertenece a tu criadero.';
        }

        if ($madreId !== null && !$this->canarios->perteneceAlCriadero($madreId, $criaderoId)) {
            $errores['madre_id'] = 'La madre indicada no pertenece a tu criadero.';
        }

        return $errores;
    }

    /**
     * Valida que un canario pertenezca al criadero y no esté ya dado de baja (RF003, RF011, DAT-004).
     *
     * @return array<string, string> mapa de errores (vacío si puede darse de baja)
     */
    public function validarCanarioParaBajaEnCriadero(int $canarioId, int $criaderoId): array
    {
        $canario = $this->canarios->buscarPorIdEnCriadero($canarioId, $criaderoId);
        if ($canario === null || $canario['estado'] === 'baja') {
            return [
                '_general' => 'El canario no pertenece a tu criadero o ya fue dado de baja.',
            ];
        }

        return [];
    }
}
