<?php

declare(strict_types=1);

namespace TuCriadero\Controllers;

use TuCriadero\Core\HttpException;
use TuCriadero\Core\Sesion;

/**
 * Base común de controladores: control de rol y aislamiento por criadero (SRS-RF003, SEG-001).
 *
 * - `exigirCriador()` y `criaderoIdDeSesion()` leen el `criadero_id` exclusivamente de la sesión
 *   y devuelven 403 si entra un administrador (no tiene criadero).
 * - `exigirAdmin()` exige rol `admin` y devuelve 403 si entra un criador.
 * - `exigirRegistroPropio()` responde 404 cuando un registro no existe o pertenece a otro criadero.
 */
abstract class BaseController
{
    /**
     * Exige sesión autenticada con rol `criador` y `criadero_id` válido en la sesión.
     * Un administrador recibe 403 (no tiene criadero). Sin sesión, redirige a `/ingresar`.
     *
     * @return array{usuario_id: int, nombre: string, rol: 'criador', criadero_id: int}
     */
    protected function exigirCriador(): array
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            $this->redirigir(ruta('ingreso'));
        }

        if ($sesion['rol'] !== 'criador') {
            throw HttpException::prohibido();
        }

        $criaderoId = $_SESSION['criadero_id'] ?? null;
        if (!is_int($criaderoId) && !(is_string($criaderoId) && ctype_digit($criaderoId))) {
            throw HttpException::sesionExpirada();
        }

        $criaderoIdInt = (int) $criaderoId;
        if ($criaderoIdInt <= 0) {
            throw HttpException::sesionExpirada();
        }

        return [
            'usuario_id' => $sesion['usuario_id'],
            'nombre' => $sesion['nombre'],
            'rol' => 'criador',
            'criadero_id' => $criaderoIdInt,
        ];
    }

    /**
     * Entrega el `criadero_id` de la sesión del criador autenticado (RF003).
     * Ningún controlador debe leer `criadero_id` del formulario ni de la URL.
     */
    protected function criaderoIdDeSesion(): int
    {
        return $this->exigirCriador()['criadero_id'];
    }

    /**
     * Exige sesión autenticada con rol `admin`.
     * Un criador recibe 403. Sin sesión, redirige a `/ingresar`.
     *
     * @return array{usuario_id: int, nombre: string, rol: 'admin'}
     */
    protected function exigirAdmin(): array
    {
        $sesion = Sesion::paraVista();
        if ($sesion === null) {
            $this->redirigir(ruta('ingreso'));
        }

        if ($sesion['rol'] !== 'admin') {
            throw HttpException::prohibido();
        }

        return [
            'usuario_id' => $sesion['usuario_id'],
            'nombre' => $sesion['nombre'],
            'rol' => 'admin',
        ];
    }

    /**
     * Responde 404 cuando el registro pedido no existe o pertenece a otro criadero (RF003, contrato §6).
     *
     * @template T of array<string, mixed>
     * @param T|null $registro
     * @return T
     */
    protected function exigirRegistroPropio(?array $registro): array
    {
        if ($registro === null) {
            throw HttpException::noEncontrado();
        }

        return $registro;
    }

    /**
     * Valida un parámetro `{id}` de ruta como entero positivo; de lo contrario responde 404.
     */
    protected function exigirIdPositivo(string $id): int
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            throw HttpException::noEncontrado();
        }

        return (int) $id;
    }

    protected function redirigir(string $url, int $codigo = 303): never
    {
        header('Location: ' . $url, true, $codigo);
        exit;
    }
}
