<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Skin;

/**
 * The skins of AdminLTE 4.10's color documentation, as slugs of their names ("Navy & sky" is "navy-sky").
 *
 * Each region: [background, theme]. A background is "body", "body-secondary" or "body-tertiary" (Bootstrap's
 * surfaces), a color name or "gradient-<color>". The theme "page" leaves the region in the color mode of the page:
 * the documentation's "light" on a surface, so the header of "default" turns dark with the page as in AdminLTE's own
 * layout; "dark" and "light" pin the region to that mode. The palette is the one the colors come from.
 */
final class Presets
{
    /**
     * @var array<string, array{header: array{string, string}, sidebar: array{string, string}, primary: string, palette: ?string}>
     */
    public const SKINS = [
        // light
        'light'        => ['header' => ['body', 'page'], 'sidebar' => ['body', 'page'], 'primary' => 'primary', 'palette' => null],
        'light-indigo' => ['header' => ['indigo', 'dark'], 'sidebar' => ['body-tertiary', 'page'], 'primary' => 'indigo', 'palette' => 'default'],
        'light-sky'    => ['header' => ['sky', 'dark'], 'sidebar' => ['body', 'page'], 'primary' => 'sky', 'palette' => 'default'],
        // semi-dark
        'default'  => ['header' => ['body', 'page'], 'sidebar' => ['body-secondary', 'dark'], 'primary' => 'primary', 'palette' => null],
        'graphite' => ['header' => ['body', 'page'], 'sidebar' => ['graphite', 'dark'], 'primary' => 'indigo', 'palette' => 'default'],
        'navy'     => ['header' => ['body', 'page'], 'sidebar' => ['navy', 'dark'], 'primary' => 'teal', 'palette' => 'default'],
        'steel'    => ['header' => ['body', 'page'], 'sidebar' => ['steel', 'dark'], 'primary' => 'sky', 'palette' => 'default'],
        'midnight' => ['header' => ['body', 'page'], 'sidebar' => ['midnight', 'dark'], 'primary' => 'violet', 'palette' => 'default'],
        'indigo'   => ['header' => ['body', 'page'], 'sidebar' => ['indigo', 'dark'], 'primary' => 'indigo', 'palette' => 'default'],
        // full dark
        'navy-sky'        => ['header' => ['sky', 'dark'], 'sidebar' => ['navy', 'dark'], 'primary' => 'sky', 'palette' => 'default'],
        'midnight-mono'   => ['header' => ['midnight', 'dark'], 'sidebar' => ['midnight', 'dark'], 'primary' => 'indigo', 'palette' => 'default'],
        'steel-mono'      => ['header' => ['steel', 'dark'], 'sidebar' => ['steel', 'dark'], 'primary' => 'sky', 'palette' => 'default'],
        'slate-teal'      => ['header' => ['teal', 'dark'], 'sidebar' => ['slate', 'dark'], 'primary' => 'teal', 'palette' => 'default'],
        'graphite-orange' => ['header' => ['orange', 'dark'], 'sidebar' => ['graphite', 'dark'], 'primary' => 'orange', 'palette' => 'default'],
        // colored and gradient
        'violet-mono'     => ['header' => ['violet', 'dark'], 'sidebar' => ['violet', 'dark'], 'primary' => 'violet', 'palette' => 'default'],
        'gradient-indigo' => ['header' => ['body', 'page'], 'sidebar' => ['gradient-indigo', 'dark'], 'primary' => 'indigo', 'palette' => 'default'],
        'gradient-teal'   => ['header' => ['body', 'page'], 'sidebar' => ['gradient-teal', 'dark'], 'primary' => 'teal', 'palette' => 'default'],
        // AdminLTE 2 and 3 (v3 palette)
        'blue'         => ['header' => ['lightblue', 'dark'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'lightblue', 'palette' => 'v3'],
        'black'        => ['header' => ['body', 'page'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'blue', 'palette' => 'v3'],
        'purple'       => ['header' => ['purple', 'dark'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'purple', 'palette' => 'v3'],
        'green'        => ['header' => ['green', 'dark'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'green', 'palette' => 'v3'],
        'red'          => ['header' => ['red', 'dark'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'red', 'palette' => 'v3'],
        'yellow'       => ['header' => ['yellow', 'light'], 'sidebar' => ['gray-dark', 'dark'], 'primary' => 'yellow', 'palette' => 'v3'],
        'blue-light'   => ['header' => ['lightblue', 'dark'], 'sidebar' => ['body', 'page'], 'primary' => 'lightblue', 'palette' => 'v3'],
        'black-light'  => ['header' => ['body', 'page'], 'sidebar' => ['body', 'page'], 'primary' => 'blue', 'palette' => 'v3'],
        'purple-light' => ['header' => ['purple', 'dark'], 'sidebar' => ['body', 'page'], 'primary' => 'purple', 'palette' => 'v3'],
        'green-light'  => ['header' => ['green', 'dark'], 'sidebar' => ['body', 'page'], 'primary' => 'green', 'palette' => 'v3'],
        'red-light'    => ['header' => ['red', 'dark'], 'sidebar' => ['body', 'page'], 'primary' => 'red', 'palette' => 'v3'],
        'yellow-light' => ['header' => ['yellow', 'light'], 'sidebar' => ['body', 'page'], 'primary' => 'yellow', 'palette' => 'v3'],
    ];
}
