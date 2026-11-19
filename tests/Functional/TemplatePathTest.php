<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * The theme's templates come before Sonata's own in the Sonata namespaces; "@!Namespace" still finds the original.
 */
final class TemplatePathTest extends KernelTestCase
{
    public function testThemeTemplatesComeBeforeSonatas(): void
    {
        $twig = self::getContainer()->get('twig');
        \assert($twig instanceof Environment);
        $loader = self::getContainer()->get('twig.loader.native_filesystem');
        \assert($loader instanceof FilesystemLoader);

        $bundle = \dirname(__DIR__, 2);
        foreach (['SonataAdmin', 'SonataForm', 'SonataTwig'] as $namespace) {
            $paths = array_map('realpath', $loader->getPaths($namespace));
            self::assertSame(realpath($bundle.'/templates/sonata/'.$namespace), $paths[0], $namespace);
            self::assertStringContainsString('/vendor/sonata-project/', (string) end($paths), $namespace);
        }

        self::assertStringStartsWith(realpath($bundle.'/templates/sonata/SonataAdmin'), $twig->load('@SonataAdmin/standard_layout.html.twig')->getSourceContext()->getPath());
        self::assertStringContainsString('/vendor/sonata-project/admin-bundle/', $twig->load('@!SonataAdmin/standard_layout.html.twig')->getSourceContext()->getPath());
    }

    public function testSkinStylesheetIsRemoved(): void
    {
        $configuration = self::getContainer()->get('sonata.admin.configuration');
        $stylesheets = $configuration->getOption('stylesheets');
        $paths = array_column($stylesheets, 'path');

        // no AdminLTE 2 skin (Sonata's option "skin" set to null by the theme)
        self::assertNull($configuration->getOption('skin'));
        self::assertContains('bundles/byteworkssonatatheme/app.css', $paths);
        self::assertSame([], array_filter($paths, static fn (string $path): bool => str_contains($path, 'admin-lte-skins') || str_contains($path, 'sonataadmin/app.css')));
    }
}
