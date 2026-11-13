<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;
use TuCriadero\Core\Aplicacion;
use TuCriadero\Core\HttpException;
use TuCriadero\Core\Sesion;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        Sesion::iniciar();
    }

    public function testUnGetNoPideToken(): void
    {
        Aplicacion::comprobarCsrf('GET', []);
        $this->addToAssertionCount(1);
    }

    public function testUnPostConElTokenDeLaSesionPasa(): void
    {
        Aplicacion::comprobarCsrf('POST', ['_csrf' => Sesion::csrf()]);
        $this->addToAssertionCount(1);
    }

    public function testUnPostSinTokenRespondeSesionExpirada(): void
    {
        try {
            Aplicacion::comprobarCsrf('POST', []);
            self::fail('Se esperaba un error 419.');
        } catch (HttpException $error) {
            self::assertSame(419, $error->codigo());
        }
    }

    public function testUnPostConOtroTokenRespondeSesionExpirada(): void
    {
        try {
            Aplicacion::comprobarCsrf('POST', ['_csrf' => str_repeat('0', 64)]);
            self::fail('Se esperaba un error 419.');
        } catch (HttpException $error) {
            self::assertSame(419, $error->codigo());
        }
    }
}
