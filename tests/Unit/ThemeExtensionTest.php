<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit;

use Byteworks\SonataThemeBundle\Twig\ThemeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeExtensionTest extends TestCase
{
    /**
     * @return iterable<array{?string, string}>
     */
    public static function provideCardClassCases(): iterable
    {
        yield 'Sonata default' => ['box box-primary', 'card card-primary card-outline'];
        yield 'AdminLTE 2 color' => ['box box-info', 'card card-info card-outline'];
        yield 'solid header' => ['box box-solid box-danger', 'card card-danger'];
        yield 'AdminLTE 2 name' => ['box box-default', 'card card-secondary card-outline'];
        yield 'other classes stay' => ['box box-success my-class', 'card card-success card-outline my-class'];
        yield 'card classes stay' => ['card card-warning', 'card card-warning'];
        yield 'empty' => [null, 'card'];
    }

    #[DataProvider('provideCardClassCases')]
    public function testCardClass(?string $boxClass, string $expected): void
    {
        self::assertSame($expected, (new ThemeExtension([]))->cardClass($boxClass));
    }

    public function testColor(): void
    {
        $extension = new ThemeExtension([]);

        self::assertSame('info', $extension->color('bg-aqua'));
        self::assertSame('danger', $extension->color('danger'));
        self::assertSame('secondary', $extension->color('default'));
        self::assertSame('secondary', $extension->color(null));
    }

    public function testUnsupportedFailsLoudly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The template "x.html.twig" is not ported to AdminLTE 4 yet.');

        (new ThemeExtension([]))->unsupported('x.html.twig');
    }
}
