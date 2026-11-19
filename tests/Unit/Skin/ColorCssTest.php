<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit\Skin;

use Byteworks\SonataThemeBundle\Skin\ColorCss;
use PHPUnit\Framework\TestCase;

final class ColorCssTest extends TestCase
{
    public function testNoColorsNoStylesheet(): void
    {
        self::assertSame('', ColorCss::generate([]));
    }

    /**
     * A dark color carries white text; the derived values are Bootstrap's (tint and shade of #0f766e).
     */
    public function testDarkColor(): void
    {
        $css = ColorCss::generate(['brand' => '#0F766E']);

        self::assertStringContainsString(':root,[data-bs-theme="light"]{--bs-brand:#0f766e;--bs-brand-rgb:15, 118, 110;--bs-brand-text-emphasis:#062f2c;', $css);
        self::assertStringContainsString('[data-bs-theme="dark"]{--bs-brand-text-emphasis:#6fada8;', $css);
        self::assertStringContainsString('.text-bg-brand{color:#ffffff!important;', $css);
        self::assertStringContainsString('.bg-gradient-brand{color:#ffffff!important;background-color:var(--bs-brand)!important;', $css);
        self::assertStringContainsString('.card-brand,.bg-brand,.text-bg-brand{--lte-card-variant-bg:var(--bs-brand);', $css);
        // one value for both modes: the dark block derives its tones from it and does not repeat the color
        self::assertStringNotContainsString('[data-bs-theme="dark"]{--bs-brand:', $css);
        // primary: the variables AdminLTE's palette sheet rewires buttons, forms etc. to; hover is 15% darker
        self::assertStringContainsString('[data-lte-primary="brand"]{--bs-primary:var(--bs-brand);--bs-primary-rgb:var(--bs-brand-rgb);', $css);
        self::assertStringContainsString('--lte-primary-hover:#0d645e;', $css);
        self::assertStringNotContainsString('--lte-primary-color:', $css);
        self::assertStringContainsString('[data-bs-theme="dark"][data-lte-primary="brand"],[data-bs-theme="dark"] [data-lte-primary="brand"]{--bs-link-color:#6fada8;', $css);
        // the opacity modifiers come last, so .bg-brand.bg-opacity-25 still works
        self::assertStringEndsWith(".text-opacity-100{--bs-text-opacity:1}\n", $css);
    }

    /**
     * A value per color mode: the token switches, and every tone derived from the value (button states, links, subtle
     * tones) is derived from the value of its mode.
     */
    public function testValuePerColorMode(): void
    {
        $css = ColorCss::generate(['logo' => ['light' => '#168666', 'dark' => '#0F6E56']]);

        self::assertStringContainsString(':root,[data-bs-theme="light"]{--bs-logo:#168666;--bs-logo-rgb:22, 134, 102;', $css);
        self::assertStringContainsString('[data-bs-theme="dark"]{--bs-logo:#0f6e56;--bs-logo-rgb:15, 110, 86;--bs-logo-text-emphasis:#6fa89a;', $css);
        self::assertStringContainsString('[data-lte-primary="logo"]{--bs-primary:var(--bs-logo);', $css);
        self::assertStringContainsString('--lte-primary-hover:#137257;', $css);
        self::assertStringContainsString('[data-bs-theme="dark"][data-lte-primary="logo"],[data-bs-theme="dark"] [data-lte-primary="logo"]{--bs-link-color:#6fa89a;', $css);
        self::assertStringContainsString('--lte-primary-hover:#0d5e49;', $css);
        self::assertStringContainsString('[data-bs-theme="dark"] .btn.bg-logo:hover,', $css);
    }

    /**
     * A light color carries black text, and its button states get lighter instead of darker.
     */
    public function testLightColor(): void
    {
        $css = ColorCss::generate(['sand' => '#ffe08a']);

        self::assertStringContainsString('.text-bg-sand{color:#000000!important;', $css);
        self::assertStringContainsString('--lte-primary-hover:#ffe59c;', $css);
        self::assertStringContainsString('--lte-primary-color:#000000;--lte-primary-color-rgb:0, 0, 0}', $css);
    }
}
