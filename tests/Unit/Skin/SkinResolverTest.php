<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit\Skin;

use Byteworks\SonataThemeBundle\Skin\SkinResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SkinResolverTest extends TestCase
{
    public function testDefaultIsAdminLtesOwnLayout(): void
    {
        self::assertSame([
            'name' => 'default',
            'palette' => 'default',
            'header' => ['class' => 'bg-body', 'theme' => null],
            'sidebar' => ['class' => 'bg-body-secondary', 'theme' => 'dark'],
            'primary' => null,
            'colors' => null,
        ], SkinResolver::resolve(self::config()));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array{class: string, theme: ?string}, array{class: string, theme: ?string}, ?string}>
     */
    public static function providePresets(): iterable
    {
        yield 'light: surfaces follow the page' => [['skin' => 'light'], ['class' => 'bg-body', 'theme' => null], ['class' => 'bg-body', 'theme' => null], null];
        yield 'navy & sky' => [['skin' => 'navy-sky'], ['class' => 'text-bg-sky', 'theme' => 'dark'], ['class' => 'text-bg-navy', 'theme' => 'dark'], 'sky'];
        yield 'gradient' => [['skin' => 'gradient-teal'], ['class' => 'bg-body', 'theme' => null], ['class' => 'bg-gradient-teal', 'theme' => 'dark'], 'teal'];
        yield 'AdminLTE 2 blue' => [['skin' => 'blue', 'palette' => 'v3'], ['class' => 'text-bg-lightblue', 'theme' => 'dark'], ['class' => 'text-bg-gray-dark', 'theme' => 'dark'], 'lightblue'];
        yield 'yellow header carries dark text' => [['skin' => 'yellow-light', 'palette' => 'v3'], ['class' => 'text-bg-yellow', 'theme' => 'light'], ['class' => 'bg-body', 'theme' => null], 'yellow'];
        yield 'own palette build' => [['skin' => 'steel', 'palette' => 'none'], ['class' => 'bg-body', 'theme' => null], ['class' => 'text-bg-steel', 'theme' => 'dark'], 'sky'];
    }

    /**
     * @param array<string, mixed>                   $options
     * @param array{class: string, theme: ?string} $header
     * @param array{class: string, theme: ?string} $sidebar
     */
    #[DataProvider('providePresets')]
    public function testPresets(array $options, array $header, array $sidebar, ?string $primary): void
    {
        $skin = SkinResolver::resolve(self::config($options));

        self::assertSame([$header, $sidebar, $primary], [$skin['header'], $skin['sidebar'], $skin['primary']]);
    }

    public function testOwnSkinWithOwnColors(): void
    {
        $skin = SkinResolver::resolve(self::config([
            'skin' => 'brand',
            'colors' => ['brand' => '#0f766e', 'sand' => '#ffe08a'],
            'skins' => ['brand' => self::skin('sand', 'brand', 'brand')],
        ]));

        // the theme follows the color: white text on the dark green, black text on the sand
        self::assertSame(['class' => 'text-bg-sand', 'theme' => 'light'], $skin['header']);
        self::assertSame(['class' => 'text-bg-brand', 'theme' => 'dark'], $skin['sidebar']);
        self::assertSame('brand', $skin['primary']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{12}$/', (string) $skin['colors']);
    }

    public function testOwnColorWithAValuePerColorMode(): void
    {
        $skin = SkinResolver::resolve(self::config([
            'skin' => 'brand',
            'colors' => ['logo' => ['light' => '#168666', 'dark' => '#0f6e56']],
            'skins' => ['brand' => self::skin('body', 'logo', 'logo')],
        ]));

        self::assertSame(['class' => 'text-bg-logo', 'theme' => 'dark'], $skin['sidebar']);
        self::assertSame('logo', $skin['primary']);
    }

    public function testOwnSkinMayPinTheTheme(): void
    {
        $skin = SkinResolver::resolve(self::config([
            'skin' => 'calm',
            'skins' => ['calm' => self::skin('body', 'body-tertiary', 'teal', 'dark', 'page')],
        ]));

        self::assertSame(['class' => 'bg-body', 'theme' => 'dark'], $skin['header']);
        self::assertSame(['class' => 'bg-body-tertiary', 'theme' => null], $skin['sidebar']);
        self::assertSame('teal', $skin['primary']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideErrors(): iterable
    {
        yield 'unknown skin' => [['skin' => 'pastel'], 'Unknown skin "pastel"'];
        yield 'v3 skin without the v3 palette' => [['skin' => 'blue'], 'needs the palette "v3"'];
        yield 'AdminLTE 4 skin with the v3 palette' => [['skin' => 'light-sky', 'palette' => 'v3'], 'needs the palette "default"'];
        yield 'unknown color' => [['skin' => 'x', 'skins' => ['x' => self::skin('body', 'mauve')]], 'unknown color "mauve"'];
        yield 'unknown primary' => [['skin' => 'x', 'skins' => ['x' => self::skin('body', 'navy', 'mauve')]], 'primary color "mauve"'];
        yield 'color of the other palette' => [['skin' => 'x', 'skins' => ['x' => self::skin('body', 'lightblue')]], 'unknown color "lightblue"'];
        yield 'own skin named like a preset' => [['skin' => 'navy', 'skins' => ['navy' => self::skin('body', 'navy')]], 'name of a preset'];
        yield 'own color named like a palette color' => [['colors' => ['sky' => '#000080']], 'exists already'];
        yield 'own color named like a Bootstrap color' => [['colors' => ['primary' => '#000080']], 'exists already'];
        yield 'own color not #rrggbb' => [['colors' => ['brand' => 'teal']], 'no color of the form #rrggbb'];
        yield 'own color name' => [['colors' => ['Brand' => '#000080']], 'lower case letters'];
        yield 'own color: dark value not #rrggbb' => [['colors' => ['brand' => ['light' => '#000080', 'dark' => 'navy']]], 'Color "brand" (dark): "navy" is no color'];
        yield 'own color: light and dark carry different text' => [['colors' => ['brand' => ['light' => '#0f6e56', 'dark' => '#1d9e75']]], 'need the same text color'];
        yield 'own palette build: unknown color needs a theme' => [['skin' => 'x', 'palette' => 'none', 'skins' => ['x' => self::skin('body', 'brand')]], 'without "theme"'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideErrors')]
    public function testErrors(array $options, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        SkinResolver::resolve(self::config($options));
    }

    public function testOwnPaletteBuildTakesUnknownColorsWithATheme(): void
    {
        $skin = SkinResolver::resolve(self::config([
            'skin' => 'x',
            'palette' => 'none',
            'skins' => ['x' => self::skin('body', 'brand', 'brand', null, 'dark')],
        ]));

        self::assertSame(['class' => 'text-bg-brand', 'theme' => 'dark'], $skin['sidebar']);
        self::assertSame('brand', $skin['primary']);
    }

    /**
     * The processed configuration with the defaults of the bundle.
     *
     * @param array<string, mixed> $options
     *
     * @return array{skin: string, palette: string, colors: array<string, string>, skins: array<string, array{header: array{background: string, theme: ?string}, sidebar: array{background: string, theme: ?string}, primary: string}>}
     */
    private static function config(array $options = []): array
    {
        return $options + ['skin' => 'default', 'palette' => 'default', 'colors' => [], 'skins' => []];
    }

    /**
     * @return array{header: array{background: string, theme: ?string}, sidebar: array{background: string, theme: ?string}, primary: string}
     */
    private static function skin(string $header, string $sidebar, string $primary = 'primary', ?string $headerTheme = null, ?string $sidebarTheme = null): array
    {
        return [
            'header' => ['background' => $header, 'theme' => $headerTheme],
            'sidebar' => ['background' => $sidebar, 'theme' => $sidebarTheme],
            'primary' => $primary,
        ];
    }
}
