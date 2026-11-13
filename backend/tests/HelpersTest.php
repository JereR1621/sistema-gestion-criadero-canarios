<?php

declare(strict_types=1);

namespace TuCriadero\Tests;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscapaHtml(): void
    {
        self::assertSame('&lt;script&gt;', \e('<script>'));
    }

    public function testFormateaUnaFechaValida(): void
    {
        self::assertSame('02/11/2026', \fecha('2026-11-02'));
    }

    public function testNoFormateaUnaFechaInvalida(): void
    {
        self::assertSame('', \fecha('2026-99-99'));
    }
}
