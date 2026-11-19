<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle;

use Byteworks\SonataThemeBundle\Controller\ColorsController;
use Byteworks\SonataThemeBundle\DependencyInjection\Compiler\TemplatePathPass;
use Byteworks\SonataThemeBundle\Skin\ColorCatalog;
use Byteworks\SonataThemeBundle\Skin\ColorCss;
use Byteworks\SonataThemeBundle\Skin\SkinResolver;
use Byteworks\SonataThemeBundle\Twig\ThemeExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * AdminLTE 4 theme for SonataAdminBundle: replaces Sonata's templates (by name, see TemplatePathPass) and assets
 * (through the sonata_admin configuration); Sonata's PHP code stays as it is.
 *
 * @phpstan-type ThemeConfig array{
 *     skin: string,
 *     palette: string,
 *     colors: array<string, string|array{light: string, dark?: ?string}>,
 *     skins: array<string, array{header: array{background: string, theme: ?string}, sidebar: array{background: string, theme: ?string}, primary: string}>,
 *     sidebar: array{mini: bool, collapsed: bool},
 * }
 */
final class ByteworksSonataThemeBundle extends AbstractBundle
{
    /**
     * Public path of the built assets (assets:install).
     */
    public const ASSETS_PATH = 'bundles/byteworkssonatatheme';

    private const ALIAS = 'byteworks_sonata_theme';

    /**
     * Stylesheets of AdminLTE's color palettes, by the option "palette".
     */
    private const PALETTE_STYLESHEETS = [ColorCatalog::PALETTE_DEFAULT => 'colors.css', ColorCatalog::PALETTE_V3 => 'colors-v3.css'];

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('skin')
                    ->info('Colors of header, sidebar and primary: a skin of AdminLTE\'s color documentation as a slug ("default", "navy", "navy-sky", "blue" ...) or an own skin from "skins".')
                    ->defaultValue('default')
                ->end()
                ->enumNode('palette')
                    ->info('AdminLTE color palette: "default" (AdminLTE 4, all colors AA with white text), "v3" (the colors of AdminLTE 3, for the skins in its style) or "none" (the application loads its own palette build).')
                    ->values([ColorCatalog::PALETTE_DEFAULT, ColorCatalog::PALETTE_V3, ColorCatalog::PALETTE_NONE])
                    ->defaultValue(ColorCatalog::PALETTE_DEFAULT)
                ->end()
                ->arrayNode('colors')
                    ->info('Own colors without a Sass build: name => "#rrggbb", or {light: "#rrggbb", dark: "#rrggbb"} for a value per color mode; served by the route of the bundle (config/routes.php).')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->beforeNormalization()->ifString()->then(static fn (string $color): array => ['light' => $color])->end()
                        ->children()
                            ->scalarNode('light')->isRequired()->end()
                            ->scalarNode('dark')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('skins')
                    ->info('Own skins. Backgrounds: "body", "body-secondary", "body-tertiary", a color name or "gradient-<color>"; theme: "light", "dark" or "page" (follows the page), by default from the color.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->append($this->region('header', 'body'))
                            ->append($this->region('sidebar', 'body-secondary'))
                            ->scalarNode('primary')->info('Primary color: "primary" (Bootstrap\'s) or a color name.')->defaultValue('primary')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('color_mode')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('default')
                            ->info('Color mode before the user picks one with the toggle; "auto" follows the operating system.')
                            ->values(['light', 'dark', 'auto'])
                            ->defaultValue('auto')
                        ->end()
                        ->booleanNode('toggle')->info('Show the color mode toggle in the header.')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('sidebar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('mini')->info('A collapsed sidebar stays visible as a bar of icons.')->defaultTrue()->end()
                        ->booleanNode('collapsed')->info('Sidebar collapsed on the first visit.')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('brand')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')->info('Link of the brand; defaults to the dashboard.')->defaultNull()->end()
                        ->scalarNode('text')->info('Text next to the logo; defaults to sonata_admin.title.')->defaultNull()->end()
                        ->arrayNode('logo')
                            ->info('Logo variants (asset paths); missing ones fall back to "light", then to sonata_admin.title_logo.')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('light')->info('Logo on a light background.')->defaultNull()->end()
                                ->scalarNode('dark')->info('Logo on a dark background.')->defaultNull()->end()
                                ->scalarNode('mini_light')->info('Symbol for the collapsed sidebar, light background.')->defaultNull()->end()
                                ->scalarNode('mini_dark')->info('Symbol for the collapsed sidebar, dark background.')->defaultNull()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('layout')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('fixed_header')->defaultTrue()->end()
                        ->booleanNode('fixed_footer')->defaultFalse()->end()
                        ->booleanNode('fullscreen')->info('Show AdminLTE\'s fullscreen toggle in the header, left of the color mode toggle.')->defaultTrue()->end()
                        ->booleanNode('compact')->info('AdminLTE\'s compact mode: lower header and brand, tighter menu entries (text, forms and tables keep their size).')->defaultFalse()->end()
                        ->enumNode('breadcrumb')
                            ->info('Place of the breadcrumb: "content" (AdminLTE: right of the page title) or "header" (Sonata: in the header bar, the page title and its actions then share one line; hidden on narrow screens).')
                            ->values(['content', 'header'])
                            ->defaultValue('content')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('select')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('enabled')
                            ->info('Enhance select boxes with Tom Select; null follows sonata_admin.options.use_select2.')
                            ->values([null, true, false])
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function region(string $name, string $background): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition($name))
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('background')->defaultValue($background)->end()
                ->enumNode('theme')->values([null, 'light', 'dark', 'page'])->defaultNull()->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // The theme's assets replace Sonata's (jQuery, Bootstrap 3, AdminLTE 2); the datepicker of the form
        // extensions is no jQuery code and stays. The stylesheet of AdminLTE's color palette follows the theme's.
        $stylesheets = [self::ASSETS_PATH . '/app.css'];
        $palette     = $this->configuredPalette($builder);
        if (isset(self::PALETTE_STYLESHEETS[$palette])) {
            $stylesheets[] = self::ASSETS_PATH . '/' . self::PALETTE_STYLESHEETS[$palette];
        }
        $stylesheets[] = 'bundles/sonataform/app.css';

        $builder->prependExtensionConfig('sonata_admin', [
            // no AdminLTE 2 skin: Sonata loads neither its stylesheet nor its class
            'options' => ['skin' => null],
            'assets'  => [
                'stylesheets' => $stylesheets,
                'javascripts' => [self::ASSETS_PATH . '/app.js', 'bundles/sonataform/app.js'],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // processed by the tree of configure(), so of this shape (the further options are passed on as they are)
        /** @var ThemeConfig $theme */
        $theme = $config;
        try {
            $skin = SkinResolver::resolve($theme);
        }
        catch (\InvalidArgumentException $exception) {
            throw new InvalidConfigurationException(self::ALIAS . ': ' . $exception->getMessage(), 0, $exception);
        }

        // the Twig global "adminlte4": the options, with the skin resolved into classes and attributes
        $options         = $theme;
        $options['skin'] = $skin;
        unset($options['skins'], $options['colors']);

        $container->services()
            ->set('byteworks_sonata_theme.twig.theme_extension', ThemeExtension::class)
                ->args([$options])
                ->tag('twig.extension')
            ->set(ColorsController::class)
                ->args([ColorCss::generate($theme['colors'])])
                ->tag('controller.service_arguments')
                ->public();
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TemplatePathPass($this->getPath() . '/templates/sonata'));
    }

    /**
     * The palette as the application configures it (the last value wins); the configuration is not processed yet.
     */
    private function configuredPalette(ContainerBuilder $builder): string
    {
        $palette = ColorCatalog::PALETTE_DEFAULT;
        foreach ($builder->getExtensionConfig(self::ALIAS) as $config) {
            if (\is_string($config['palette'] ?? null)) {
                $palette = $config['palette'];
            }
        }

        return $palette;
    }
}
