<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Puts the theme's templates in front of Sonata's own, per Twig namespace, but behind the application's overrides.
 *
 * TwigBundle registers the paths of a namespace in this order: paths from twig.paths, the application's override
 * directory (templates/bundles/<Bundle>/), the bundle's own directory; then the same bundle directory once more as
 * "!<Namespace>". The theme's directory goes right before the bundle's own directory, so "@SonataAdmin/x" finds the
 * theme's template unless the application overrides it, and "@!SonataAdmin/x" still finds Sonata's original.
 * Namespaces of bundles that are not installed are skipped.
 */
final class TemplatePathPass implements CompilerPassInterface
{
    public const NAMESPACES = ['SonataAdmin', 'SonataDoctrineORMAdmin', 'SonataForm', 'SonataTwig', 'SonataBlock'];

    public function __construct(
        private string $templateDir,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.loader.native_filesystem')) {
            return;
        }

        $loader = $container->getDefinition('twig.loader.native_filesystem');
        $calls  = array_values($loader->getMethodCalls());

        foreach (self::NAMESPACES as $namespace) {
            $directory = $this->templateDir . '/' . $namespace;
            if (!is_dir($directory)) {
                continue;
            }

            $bundleDirectoryCall = null;
            foreach ($calls as $index => $call) {
                if (\is_array($call) && 'addPath' === ($call[0] ?? null) && \is_array($call[1] ?? null) && $namespace === ($call[1][1] ?? null)) {
                    $bundleDirectoryCall = $index;
                }
            }

            if (null === $bundleDirectoryCall) {
                continue;
            }

            array_splice($calls, $bundleDirectoryCall, 0, [['addPath', [$directory, $namespace]]]);
        }

        $loader->setMethodCalls($calls);
    }
}
