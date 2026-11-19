<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Skin;

/**
 * The stylesheet of the application's own colors (option "colors"), for projects without a Sass build: per color what
 * AdminLTE's palette sheet emits per palette color (src/scss/colors/_colors.scss and _primary.scss of admin-lte 4.10):
 * the tokens for both color modes, the utility classes, the card, callout and direct chat hooks, and the
 * data-lte-primary preset. The components that read the primary variables are rewired by the palette sheet, which
 * loads before this one.
 *
 * A color may have its own value for the dark color mode. Then the token --bs-<color> and everything derived from
 * the value (hover and active shades, link colors, subtle tones) switch with the color mode; the classes read the
 * token instead of a fixed value.
 */
final class ColorCss
{
    /**
     * Bootstrap's button state amounts: [hover background, hover border, active background, active border], each as
     * [shade, tint].
     */
    private const BUTTON_STATES = [
        'primary-hover'         => [.15, .15],
        'primary-hover-border'  => [.2, .1],
        'primary-active'        => [.2, .2],
        'primary-active-border' => [.25, .1],
    ];

    private const DARK = '[data-bs-theme="dark"]';

    /**
     * @param array<string, string|array{light: string, dark?: ?string}> $colors name => #rrggbb, or a value per mode
     */
    public static function generate(array $colors): string
    {
        if ([] === $colors) {
            return '';
        }

        $css = [];
        foreach (ColorCatalog::normalizeOwnColors($colors) as $name => ['light' => $light, 'dark' => $dark]) {
            $css[] = self::color($name, $light, $dark ?? $light);
        }

        // as AdminLTE: the opacity modifiers again after the utilities above, so they still win
        $css[] = '.bg-opacity-10{--bs-bg-opacity:.1}.bg-opacity-25{--bs-bg-opacity:.25}.bg-opacity-50{--bs-bg-opacity:.5}'
            . '.bg-opacity-75{--bs-bg-opacity:.75}.bg-opacity-100{--bs-bg-opacity:1}'
            . '.border-opacity-10{--bs-border-opacity:.1}.border-opacity-25{--bs-border-opacity:.25}.border-opacity-50{--bs-border-opacity:.5}'
            . '.border-opacity-75{--bs-border-opacity:.75}.border-opacity-100{--bs-border-opacity:1}'
            . '.text-opacity-25{--bs-text-opacity:.25}.text-opacity-50{--bs-text-opacity:.5}.text-opacity-75{--bs-text-opacity:.75}'
            . '.text-opacity-100{--bs-text-opacity:1}';

        return implode("\n", $css) . "\n";
    }

    /**
     * The rules of one color; $light and $dark are the same for a color without its own dark value.
     */
    private static function color(string $name, string $light, string $dark): string
    {
        // both values carry the same text (checked by ColorCatalog)
        $text    = Color::contrastText($light);
        $textRgb = Color::rgbList($text);
        $perMode = $light !== $dark;

        $rules = [
            // tokens: the color, its channels and the subtle / emphasis tones, per color mode as Bootstrap derives them
            ":root,[data-bs-theme=\"light\"]{--bs-$name:$light;--bs-$name-rgb:" . Color::rgbList($light) . ';'
                . "--bs-$name-text-emphasis:" . Color::shade($light, .6) . ";--bs-$name-bg-subtle:" . Color::tint($light, .8) . ';'
                . "--bs-$name-border-subtle:" . Color::tint($light, .6) . '}',
            self::DARK . '{' . ($perMode ? "--bs-$name:$dark;--bs-$name-rgb:" . Color::rgbList($dark) . ';' : '')
                . "--bs-$name-text-emphasis:" . Color::tint($dark, .4) . ";--bs-$name-bg-subtle:" . Color::shade($dark, .8) . ';'
                . "--bs-$name-border-subtle:" . Color::shade($dark, .4) . '}',
            // utilities
            ".bg-$name{--bs-bg-opacity:1;background-color:rgba(var(--bs-$name-rgb),var(--bs-bg-opacity))!important}",
            ".text-$name{--bs-text-opacity:1;color:rgba(var(--bs-$name-rgb),var(--bs-text-opacity))!important}",
            ".border-$name{--bs-border-opacity:1;border-color:rgba(var(--bs-$name-rgb),var(--bs-border-opacity))!important}",
            ".text-bg-$name{color:$text!important;background-color:RGBA(var(--bs-$name-rgb),var(--bs-bg-opacity,1))!important}",
            ".link-$name{color:RGBA(var(--bs-$name-rgb),var(--bs-link-opacity,1))!important;"
                . "text-decoration-color:RGBA(var(--bs-$name-rgb),var(--bs-link-underline-opacity,1))!important}",
            self::linkHover(".link-$name", $light),
            ".bg-gradient-$name{color:$text!important;background-color:var(--bs-$name)!important;background-image:var(--bs-gradient)!important}",
            // component hooks
            ".card-$name,.bg-$name,.text-bg-$name{--lte-card-variant-bg:var(--bs-$name);--lte-card-variant-bg-rgb:var(--bs-$name-rgb);"
                . "--lte-card-variant-color:$text;--lte-card-variant-color-rgb:$textRgb}",
            ".callout-$name{--lte-callout-color:var(--bs-$name-text-emphasis);--lte-callout-bg:var(--bs-$name-bg-subtle);"
                . "--lte-callout-border:var(--bs-$name-border-subtle);--lte-callout-link:var(--bs-$name-text-emphasis);"
                . "--lte-callout-code-color:var(--bs-$name-text-emphasis);--bs-callout-link-color:var(--bs-$name-text-emphasis)}",
            ".direct-chat-$name{--lte-direct-chat-color:$text;--lte-direct-chat-bg:var(--bs-$name)}",
            self::buttonStates('', $name, $light),
            // data-lte-primary
            self::primary($name, $light, $text),
            // links in the dark color mode: Bootstrap's tint of the (dark) value
            self::DARK . "[data-lte-primary=\"$name\"]," . self::DARK . " [data-lte-primary=\"$name\"]{" . self::darkLinks($dark)
                . ($perMode ? self::primaryStates($dark, $text) : '') . '}',
        ];

        if ($perMode) {
            $rules[] = self::linkHover(self::DARK . " .link-$name", $dark);
            $rules[] = self::buttonStates(self::DARK . ' ', $name, $dark);
        }

        return implode("\n", $rules);
    }

    private static function linkHover(string $selector, string $color): string
    {
        $hover = Color::rgbList(Color::shade($color, .2));

        return "$selector:hover,$selector:focus{color:RGBA($hover,var(--bs-link-opacity,1))!important;"
            . "text-decoration-color:RGBA($hover,var(--bs-link-underline-opacity,1))!important}";
    }

    /**
     * Hover and active shades of buttons carrying the color as background (".btn.bg-navy", the v3 idiom).
     */
    private static function buttonStates(string $scope, string $name, string $color): string
    {
        $selectors = static fn (string $state): string => implode(',', array_map(
            static fn (string $class): string => $scope . str_replace('&', $class, $state),
            [".btn.bg-$name", ".btn.text-bg-$name", ".btn.bg-gradient-$name"],
        ));

        return $selectors('&:hover,&:focus-visible') . '{background-color:' . Color::shade($color, .1) . '!important}'
            . "\n" . $selectors('&:active,&.active') . '{background-color:' . Color::shade($color, .15) . '!important}';
    }

    private static function primary(string $name, string $light, string $text): string
    {
        $linkHover = Color::shade($light, .2);
        $css       = "[data-lte-primary=\"$name\"]{--bs-primary:var(--bs-$name);--bs-primary-rgb:var(--bs-$name-rgb);"
            . "--bs-primary-text-emphasis:var(--bs-$name-text-emphasis);--bs-primary-bg-subtle:var(--bs-$name-bg-subtle);"
            . "--bs-primary-border-subtle:var(--bs-$name-border-subtle);--bs-link-color:var(--bs-$name);"
            . "--bs-link-color-rgb:var(--bs-$name-rgb);--bs-link-hover-color:$linkHover;--bs-link-hover-color-rgb:" . Color::rgbList($linkHover)
            . ';' . self::primaryStates($light, $text);
        if (Color::WHITE !== $text) {
            $css .= "--lte-primary-color:$text;--lte-primary-color-rgb:" . Color::rgbList($text) . ';';
        }

        return rtrim($css, ';') . '}';
    }

    /**
     * Button states of the primary: Bootstrap darkens a color that carries white text and lightens one that carries
     * dark text.
     */
    private static function primaryStates(string $color, string $text): string
    {
        $css = '';
        foreach (self::BUTTON_STATES as $variable => [$shade, $tint]) {
            $css .= "--lte-$variable:" . (Color::WHITE === $text ? Color::shade($color, $shade) : Color::tint($color, $tint)) . ';';
        }

        return $css;
    }

    private static function darkLinks(string $color): string
    {
        $link  = Color::tint($color, .4);
        $hover = Color::tint($link, .2);

        return "--bs-link-color:$link;--bs-link-color-rgb:" . Color::rgbList($link) . ";--bs-link-hover-color:$hover;"
            . '--bs-link-hover-color-rgb:' . Color::rgbList($hover) . ';';
    }
}
