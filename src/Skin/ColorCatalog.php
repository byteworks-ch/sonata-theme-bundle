<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Skin;

/**
 * The color names a skin can use, with the color of text on them: Bootstrap's theme colors, the colors of the loaded
 * AdminLTE palette and the application's own colors. The values follow AdminLTE's sources (src/scss/colors and
 * adminlte-colors-v3.scss of admin-lte 4.10).
 */
final class ColorCatalog
{
    public const PALETTE_DEFAULT = 'default';
    public const PALETTE_V3      = 'v3';
    public const PALETTE_NONE    = 'none';

    /**
     * Bootstrap's theme colors and the text Bootstrap compiles on them.
     */
    private const BOOTSTRAP = [
        'primary'   => ['#0d6efd', Color::WHITE],
        'secondary' => ['#6c757d', Color::WHITE],
        'success'   => ['#198754', Color::WHITE],
        'info'      => ['#0dcaf0', Color::BLACK],
        'warning'   => ['#ffc107', Color::BLACK],
        'danger'    => ['#dc3545', Color::WHITE],
        'light'     => ['#f8f9fa', Color::BLACK],
        'dark'      => ['#212529', Color::WHITE],
    ];

    /**
     * The palettes of AdminLTE 4 ("default", every color AA with white text) and of AdminLTE 3 ("v3").
     */
    private const PALETTES = [
        self::PALETTE_DEFAULT => [
            'orange' => '#c84e10', 'amber' => '#a56710', 'olive' => '#5f7f0f', 'teal' => '#12827d', 'sky' => '#127caf',
            'indigo' => '#6f60ea', 'violet' => '#9553db', 'fuchsia' => '#b347be', 'pink' => '#cd388d', 'navy' => '#1d2d4c',
            'steel'  => '#3a4860', 'slate' => '#566577', 'graphite' => '#32363c', 'midnight' => '#1e1d2d',
        ],
        self::PALETTE_V3 => [
            'lightblue' => '#3c8dbc', 'navy' => '#001f3f', 'olive' => '#3d9970', 'lime' => '#01ff70', 'fuchsia' => '#f012be',
            'maroon'    => '#d81b60', 'blue' => '#007bff', 'indigo' => '#6610f2', 'purple' => '#6f42c1', 'pink' => '#e83e8c',
            'red'       => '#dc3545', 'orange' => '#fd7e14', 'yellow' => '#ffc107', 'green' => '#28a745', 'teal' => '#20c997',
            'cyan'      => '#17a2b8', 'gray' => '#6c757d', 'gray-dark' => '#343a40',
        ],
        self::PALETTE_NONE => [],
    ];

    /**
     * @var array<string, string> name => color of text on it
     */
    private array $texts = [];

    /**
     * @param array<string, string|array{light: string, dark?: ?string}> $ownColors name => #rrggbb, or a value per
     *                                                                              color mode
     */
    public function __construct(
        private readonly string $palette,
        array $ownColors = [],
    ) {
        if (!\array_key_exists($palette, self::PALETTES)) {
            throw new \InvalidArgumentException(\sprintf('Unknown palette "%s".', $palette));
        }

        foreach (self::BOOTSTRAP as $name => [, $text]) {
            $this->texts[$name] = $text;
        }
        foreach (self::PALETTES[$palette] as $name => $color) {
            $this->texts[$name] = self::PALETTE_V3 === $palette ? Color::yiqText($color) : Color::contrastText($color);
        }
        foreach ($ownColors as $name => $color) {
            if (1 !== preg_match('/^[a-z][a-z0-9-]*$/', (string) $name)) {
                throw new \InvalidArgumentException(\sprintf('Color name "%s": lower case letters, digits and "-", starting with a letter.', $name));
            }
            if (isset($this->texts[$name])) {
                throw new \InvalidArgumentException(\sprintf('Color "%s" exists already (Bootstrap or the palette "%s"); choose another name.', $name, $palette));
            }
        }
        foreach (self::normalizeOwnColors($ownColors) as $name => ['light' => $light, 'dark' => $dark]) {
            $text = Color::contrastText($light);
            // the theme of a region on the color (data-bs-theme) is the same in both color modes
            if (null !== $dark && Color::contrastText($dark) !== $text) {
                throw new \InvalidArgumentException(\sprintf('Color "%s": the values for light (%s) and dark (%s) need the same text color; one carries white text, the other black.', $name, $light, $dark));
            }
            $this->texts[$name] = $text;
        }
    }

    /**
     * Own colors as {light, dark}: a single value is the light one, without an own dark value. Every value is checked
     * and in lower case.
     *
     * @param array<array-key, mixed> $ownColors name => #rrggbb or {light, dark?}, as configured (not checked yet)
     *
     * @return array<string, array{light: string, dark: ?string}>
     */
    public static function normalizeOwnColors(array $ownColors): array
    {
        $normalized = [];
        foreach ($ownColors as $name => $color) {
            if (\is_array($color)) {
                $dark                       = $color['dark'] ?? null;
                $normalized[(string) $name] = [
                    'light' => self::checkedColor($name, $color['light'] ?? null, 'light'),
                    'dark'  => null === $dark ? null : self::checkedColor($name, $dark, 'dark'),
                ];
            }
            else {
                $normalized[(string) $name] = ['light' => self::checkedColor($name, $color, null), 'dark' => null];
            }
        }

        return $normalized;
    }

    /**
     * A configured color value as #rrggbb in lower case; the mode is named in the error of a value per color mode.
     */
    private static function checkedColor(int|string $name, mixed $value, ?string $mode): string
    {
        if (!\is_string($value) || !Color::isValid($value)) {
            throw new \InvalidArgumentException(\sprintf('Color "%s"%s: "%s" is no color of the form #rrggbb.', $name, null === $mode ? '' : ' (' . $mode . ')', \is_string($value) ? $value : get_debug_type($value)));
        }

        return strtolower($value);
    }

    public function palette(): string
    {
        return $this->palette;
    }

    public function has(string $name): bool
    {
        return isset($this->texts[$name]);
    }

    /**
     * Whether the color carries light text, so a region on it needs data-bs-theme="dark".
     */
    public function takesLightText(string $name): bool
    {
        return Color::WHITE === ($this->texts[$name] ?? throw new \InvalidArgumentException(\sprintf('Unknown color "%s".', $name)));
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->texts);
    }

    /**
     * The names of a palette's colors, for a palette that is not loaded.
     *
     * @return list<string>
     */
    public static function paletteColors(string $palette): array
    {
        return array_keys(self::PALETTES[$palette] ?? []);
    }
}
