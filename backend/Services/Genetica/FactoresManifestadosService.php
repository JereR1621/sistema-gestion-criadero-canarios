<?php

declare(strict_types=1);

namespace TuCriadero\Services\Genetica;

use DomainException;
use TuCriadero\Core\HttpException;
use TuCriadero\Models\CanarioModel;
use TuCriadero\Services\CatalogoService;

/**
 * Obtiene los factores genéticos que manifiesta un canario a partir de la composición
 * de su variedad en el catálogo precargado (SRS-RF033, DAT-001, docs/backend.md §3 y §5).
 *
 * Los factores manifestados nunca se guardan en el canario ni se piden al criador:
 * se calculan siempre desde `variedad_factor` a través de los modelos/servicios del catálogo.
 * No consulta la base de datos directamente.
 */
final class FactoresManifestadosService
{
    public function __construct(
        private readonly CatalogoService $catalogo = new CatalogoService(),
        private readonly CanarioModel $canarios = new CanarioModel(),
    ) {
    }

    /**
     * Obtiene los factores completos (con `id`, `nombre`, `tipo_herencia` y `dosis`)
     * que manifiesta una variedad del catálogo (uso interno de genética: RF033, RF034, RF035–RF039).
     *
     * Si la variedad es null o cadena vacía ("sin determinar", RF007), devuelve [].
     * Si la variedad es de forma normal (0 mutaciones, ej. D.004 o D.100), devuelve [].
     *
     * @return list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function obtenerDesdeVariedad(?string $variedadCodigo): array
    {
        if ($variedadCodigo === null) {
            return [];
        }

        $codigo = trim($variedadCodigo);
        if ($codigo === '') {
            return [];
        }

        $variedad = $this->catalogo->obtenerVariedad($codigo);
        if ($variedad === null) {
            throw new DomainException("La variedad «{$codigo}» no existe en el catálogo.");
        }

        return $variedad['factores'];
    }

    /**
     * Obtiene los factores manifestados para `CanarioFicha.factores_manifestados`
     * (`docs/contrato-back-front.md` §5: `[['nombre' => ..., 'tipo_herencia' => ...], ...]`), más la `dosis`:
     * sin ella, la ficha no distingue jaspe SD (dosis 1) de DD (dosis 2) (backend.md §3 y §5).
     *
     * @return list<array{nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function paraFichaDesdeVariedad(?string $variedadCodigo): array
    {
        return self::paraFicha($this->obtenerDesdeVariedad($variedadCodigo));
    }

    /**
     * Obtiene los factores manifestados de un canario a partir de su clave `variedad_codigo`.
     * Ignora cualquier lista de factores enviada por el usuario (RF033: sin pedírselos al criador).
     *
     * @param array<string, mixed> $canario
     * @return list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function obtenerParaCanario(array $canario): array
    {
        $codigo = isset($canario['variedad_codigo']) && is_string($canario['variedad_codigo'])
            ? $canario['variedad_codigo']
            : null;

        return $this->obtenerDesdeVariedad($codigo);
    }

    /**
     * Obtiene los factores completos que manifiesta un canario del criadero indicado (RF003, RF033).
     * El `criadero_id` lo pasa el controlador, tomado de la sesión con el control común de acceso (#17).
     * Responde 404 si el canario no existe o pertenece a otro criadero.
     *
     * @return list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function obtenerPorCanarioId(int $canarioId, int $criaderoId): array
    {
        $canario = $this->canarios->buscarPorIdEnCriadero($canarioId, $criaderoId);
        if ($canario === null) {
            throw HttpException::noEncontrado();
        }

        return $this->obtenerDesdeVariedad($canario['variedad_codigo']);
    }

    /**
     * Obtiene `CanarioFicha.factores_manifestados` (con la dosis) para un canario del criadero indicado (RF003, RF033).
     *
     * @return list<array{nombre: string, tipo_herencia: string, dosis: int}>
     */
    public function paraFichaPorCanarioId(int $canarioId, int $criaderoId): array
    {
        return self::paraFicha($this->obtenerPorCanarioId($canarioId, $criaderoId));
    }

    /**
     * @param list<array{id: int, nombre: string, tipo_herencia: string, dosis: int}> $factores
     * @return list<array{nombre: string, tipo_herencia: string, dosis: int}>
     */
    private static function paraFicha(array $factores): array
    {
        return array_map(
            static fn (array $factor): array => [
                'nombre' => $factor['nombre'],
                'tipo_herencia' => $factor['tipo_herencia'],
                'dosis' => $factor['dosis'],
            ],
            $factores
        );
    }
}
