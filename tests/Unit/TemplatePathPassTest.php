<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit;

use Byteworks\SonataThemeBundle\DependencyInjection\Compiler\TemplatePathPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TemplatePathPassTest extends TestCase
{
    public function testInsertsTheThemeBetweenTheApplicationOverrideAndTheBundle(): void
    {
        $container = new ContainerBuilder();
        $loader = new Definition();
        $loader->addMethodCall('addPath', ['/app/templates/bundles/SonataAdminBundle', 'SonataAdmin']);
        $loader->addMethodCall('addPath', ['/vendor/sonata/views', 'SonataAdmin']);
        $loader->addMethodCall('addPath', ['/vendor/sonata/views', '!SonataAdmin']);
        $loader->addMethodCall('addPath', ['/vendor/other/views', 'Other']);
        $container->setDefinition('twig.loader.native_filesystem', $loader);

        (new TemplatePathPass(\dirname(__DIR__, 2).'/templates/sonata'))->process($container);

        $paths = array_map(static fn (array $call): string => $call[1][1].' '.$call[1][0], $loader->getMethodCalls());
        self::assertSame([
            'SonataAdmin /app/templates/bundles/SonataAdminBundle',
            'SonataAdmin '.\dirname(__DIR__, 2).'/templates/sonata/SonataAdmin',
            'SonataAdmin /vendor/sonata/views',
            '!SonataAdmin /vendor/sonata/views',
            'Other /vendor/other/views',
        ], $paths);
    }

    public function testSkipsNamespacesOfBundlesThatAreNotInstalled(): void
    {
        $container = new ContainerBuilder();
        $loader = new Definition();
        $loader->addMethodCall('addPath', ['/vendor/sonata/views', 'SonataAdmin']);
        $container->setDefinition('twig.loader.native_filesystem', $loader);

        (new TemplatePathPass(\dirname(__DIR__, 2).'/templates/sonata'))->process($container);

        $namespaces = array_map(static fn (array $call): string => $call[1][1], $loader->getMethodCalls());
        self::assertSame(['SonataAdmin', 'SonataAdmin'], $namespaces);
    }
}
