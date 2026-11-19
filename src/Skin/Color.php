<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Skin;

/**
 * Color arithmetic as Bootstrap's Sass does it (tint-color, shade-color, color-contrast), so colors configured
 * without a Sass build get the same derived values as the colors AdminLTE compiles.
 */
final class Color
{
    public const WHITE = '#ffffff';
    public const BLACK = '#000000';

    /**
     * A color as "#rrggbb" (lower case); anything else is not accepted.
     */
    public static function isValid(string $color): bool
    {
        return 1 === preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    /**
     * @return array{int, int, int}
     */
    public static function rgb(string $color): array
    {
        if (!self::isValid($color)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is no color of the form #rrggbb.', $color));
        }

        return [(int) hexdec(substr($color, 1, 2)), (int) hexdec(substr($color, 3, 2)), (int) hexdec(substr($color, 5, 2))];
    }

    /**
     * The channels as CSS expects them in Bootstrap's "-rgb" variables: "r, g, b".
     */
    public static function rgbList(string $color): string
    {
        return implode(', ', self::rgb($color));
    }

    /**
     * Sass's mix(): $weight of $with, the rest of $color, each channel rounded.
     */
    public static function mix(string $color, string $with, float $weight): string
    {
        $a        = self::rgb($color);
        $b        = self::rgb($with);
        $channels = [];
        for ($i = 0; $i < 3; ++$i) {
            $channels[] = (int) round($b[$i] * $weight + $a[$i] * (1 - $weight));
        }

        return \sprintf('#%02x%02x%02x', ...$channels);
    }

    /**
     * Bootstrap's tint-color(): mixed with white.
     */
    public static function tint(string $color, float $weight): string
    {
        return self::mix($color, self::WHITE, $weight);
    }

    /**
     * Bootstrap's shade-color(): mixed with black.
     */
    public static function shade(string $color, float $weight): string
    {
        return self::mix($color, self::BLACK, $weight);
    }

    /**
     * Relative luminance as in Bootstrap's luminance() (and WCAG).
     */
    public static function luminance(string $color): float
    {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value < .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
        }, self::rgb($color));

        return .2126 * $channels[0] + .7152 * $channels[1] + .0722 * $channels[2];
    }

    public static function contrastRatio(string $background, string $foreground): float
    {
        $l1 = self::luminance($background);
        $l2 = self::luminance($foreground);

        return $l1 > $l2 ? ($l1 + .05) / ($l2 + .05) : ($l2 + .05) / ($l1 + .05);
    }

    /**
     * Bootstrap's color-contrast(): white from the minimum ratio on, else black from the minimum ratio on, else the
     * better of the two.
     */
    public static function contrastText(string $background, float $minimumRatio = 4.5): string
    {
        $best      = self::WHITE;
        $bestRatio = 0.0;
        foreach ([self::WHITE, self::BLACK] as $text) {
            $ratio = self::contrastRatio($background, $text);
            if ($ratio >= $minimumRatio) {
                return $text;
            }
            if ($ratio > $bestRatio) {
                $best      = $text;
                $bestRatio = $ratio;
            }
        }

        return $best;
    }

    /**
     * AdminLTE 3's YIQ rule, which its v3 palette keeps: dark text (#1f2d3d) from a brightness of 150 on.
     */
    public static function yiqText(string $background): string
    {
        [$r, $g, $b] = self::rgb($background);

        return ($r * 299 + $g * 587 + $b * 114) / 1000 >= 150 ? '#1f2d3d' : self::WHITE;
    }
}
