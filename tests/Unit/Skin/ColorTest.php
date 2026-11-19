<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit\Skin;

use Byteworks\SonataThemeBundle\Skin\Color;
use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    /**
     * The values Bootstrap 5.3 compiles from its primary #0d6efd (--bs-primary-bg-subtle, -border-subtle,
     * -text-emphasis and the dark mode's -text-emphasis).
     */
    public function testTintAndShadeGiveBootstrapsValues(): void
    {
        self::assertSame('#cfe2ff', Color::tint('#0d6efd', .8));
        self::assertSame('#9ec5fe', Color::tint('#0d6efd', .6));
        self::assertSame('#052c65', Color::shade('#0d6efd', .6));
        self::assertSame('#6ea8fe', Color::tint('#0d6efd', .4));
        self::assertSame('#0d6efd', Color::mix('#0D6EFD', '#000000', 0));
    }

    public function testContrastTextFollowsBootstrapsRule(): void
    {
        // AdminLTE's "sky" carries white text at 4.6:1, Bootstrap's warning and light carry black
        self::assertSame(Color::WHITE, Color::contrastText('#127caf'));
        self::assertSame(Color::BLACK, Color::contrastText('#ffc107'));
        self::assertSame(Color::BLACK, Color::contrastText('#f8f9fa'));
        // AdminLTE 2's blue: white reaches only 3.7:1, black 5.7:1
        self::assertEqualsWithDelta(3.67, Color::contrastRatio('#3c8dbc', Color::WHITE), .01);
        self::assertSame(Color::BLACK, Color::contrastText('#3c8dbc'));
    }

    public function testYiqTextIsAdminLte3sRule(): void
    {
        self::assertSame('#1f2d3d', Color::yiqText('#ffc107'));
        self::assertSame(Color::WHITE, Color::yiqText('#3c8dbc'));
    }

    public function testOnlyRrggbbIsAColor(): void
    {
        self::assertTrue(Color::isValid('#0F766E'));
        self::assertFalse(Color::isValid('#0f7'));
        self::assertFalse(Color::isValid('teal'));
        self::assertSame('15, 118, 110', Color::rgbList('#0f766e'));

        $this->expectException(\InvalidArgumentException::class);
        Color::rgb('rgb(1, 2, 3)');
    }
}
