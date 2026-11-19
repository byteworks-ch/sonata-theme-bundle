<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Skin;

/**
 * Resolves the configured skin (a preset or an own skin) into what the layouts render: classes and data-bs-theme of
 * header and sidebar, data-lte-primary of the page, and the version of the own colors' stylesheet. Every name is
 * checked against the colors that are loaded; a wrong one fails when the container is built.
 */
final class SkinResolver
{
    private const SURFACES = ['body', 'body-secondary', 'body-tertiary'];

    /**
     * @param array{skin: string, palette: string, colors: array<string, string|array{light: string, dark?: ?string}>, skins: array<string, array{header: array{background: string, theme: ?string}, sidebar: array{background: string, theme: ?string}, primary: string}>} $config
     *
     * @return array{name: string, palette: string, header: array{class: string, theme: ?string}, sidebar: array{class: string, theme: ?string}, primary: ?string, colors: ?string}
     */
    public static function resolve(array $config): array
    {
        $catalog = new ColorCatalog($config['palette'], $config['colors']);

        foreach (array_keys($config['skins']) as $name) {
            if (isset(Presets::SKINS[$name])) {
                throw new \InvalidArgumentException(\sprintf('The own skin "%s" has the name of a preset; choose another name.', $name));
            }
        }

        $name = $config['skin'];
        if (isset($config['skins'][$name])) {
            $skin    = $config['skins'][$name];
            $header  = [$skin['header']['background'], $skin['header']['theme']];
            $sidebar = [$skin['sidebar']['background'], $skin['sidebar']['theme']];
            $primary = $skin['primary'];
        }
        elseif (isset(Presets::SKINS[$name])) {
            $preset = Presets::SKINS[$name];
            if (null !== $preset['palette'] && ColorCatalog::PALETTE_NONE !== $catalog->palette() && $preset['palette'] !== $catalog->palette()) {
                throw new \InvalidArgumentException(\sprintf('The skin "%s" needs the palette "%s" (option "palette").', $name, $preset['palette']));
            }
            [$header, $sidebar, $primary] = [$preset['header'], $preset['sidebar'], $preset['primary']];
        }
        else {
            throw new \InvalidArgumentException(\sprintf('Unknown skin "%s". Presets: %s%s.', $name, implode(', ', array_keys(Presets::SKINS)), [] === $config['skins'] ? '' : '; own skins: ' . implode(', ', array_keys($config['skins']))));
        }

        return [
            'name'    => $name,
            'palette' => $catalog->palette(),
            'header'  => self::region('header', $header[0], $header[1], $catalog),
            'sidebar' => self::region('sidebar', $sidebar[0], $sidebar[1], $catalog),
            'primary' => self::primary($primary, $catalog),
            'colors'  => [] === $config['colors'] ? null : substr(hash('sha256', serialize($config['colors'])), 0, 12),
        ];
    }

    /**
     * @return array{class: string, theme: ?string}
     */
    private static function region(string $region, string $background, ?string $theme, ColorCatalog $catalog): array
    {
        if (\in_array($background, self::SURFACES, true)) {
            return ['class' => 'bg-' . $background, 'theme' => 'page' === $theme ? null : $theme];
        }

        $gradient = str_starts_with($background, 'gradient-');
        $color    = $gradient ? substr($background, \strlen('gradient-')) : $background;
        if (!$catalog->has($color)) {
            // the application's own palette build: the bundle does not know its colors
            if (ColorCatalog::PALETTE_NONE !== $catalog->palette() || null === $theme) {
                throw new \InvalidArgumentException(\sprintf('The %s of the skin uses the unknown color "%s"%s. Known: %s.', $region, $color, ColorCatalog::PALETTE_NONE === $catalog->palette() ? ' without "theme" (with the palette "none" the bundle cannot tell whether it takes light text)' : '', implode(', ', $catalog->names())));
            }
        }

        $theme ??= $catalog->takesLightText($color) ? 'dark' : 'light';

        return ['class' => ($gradient ? 'bg-gradient-' : 'text-bg-') . $color, 'theme' => 'page' === $theme ? null : $theme];
    }

    /**
     * The value of data-lte-primary; none for Bootstrap's own primary.
     */
    private static function primary(string $primary, ColorCatalog $catalog): ?string
    {
        if ('primary' === $primary) {
            return null;
        }
        if (!$catalog->has($primary) && ColorCatalog::PALETTE_NONE !== $catalog->palette()) {
            throw new \InvalidArgumentException(\sprintf('The primary color "%s" of the skin is unknown. Known: %s.', $primary, implode(', ', $catalog->names())));
        }

        return $primary;
    }
}
