<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * assets/package.json and assets/controllers.json describe the Stimulus controllers of the theme for tools (the Symfony
 * language tools, IDEs), so a project's template may use them: exactly the controllers assets/js/app.js registers,
 * under the same names and with their sources. The theme's script registers them, not the project. So the bundle must
 * not become a Symfony UX package (composer keyword "symfony-ux"): Symfony Flex would add the controllers to the
 * project's package.json and controllers.json, and the project's Stimulus application would start them a second time.
 */
final class StimulusControllersTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    public function testThePackageDescribesTheControllersTheScriptRegisters(): void
    {
        $registered = self::registeredControllers();
        self::assertNotEmpty($registered);

        $described = [];
        foreach (self::json('assets/package.json')['symfony']['controllers'] as $key => $controller) {
            self::assertSame($key, $controller['name'], 'The key of a controller is its name.');
            self::assertFileExists(self::ROOT.'/assets/'.$controller['main']);
            $described[$controller['name']] = $controller['main'];
        }
        ksort($described);

        self::assertSame($registered, $described);
    }

    public function testTheBundlesControllersJsonEnablesEveryDescribedController(): void
    {
        $controllers = self::json('assets/controllers.json')['controllers'];
        self::assertSame([self::packageName()], array_keys($controllers));

        $enabled = array_keys(array_filter($controllers[self::packageName()], static fn (array $controller): bool => true === $controller['enabled']));
        sort($enabled);

        self::assertSame(array_keys(self::registeredControllers()), $enabled);
    }

    public function testTheToolsFindTheComposerPackageAndFlexLeavesTheProjectAlone(): void
    {
        // the tools take the name in controllers.json without its "@" as the name of the Composer package
        self::assertSame(self::packageName(), self::json('assets/package.json')['name']);

        self::assertNotContains('symfony-ux', self::json('composer.json')['keywords'] ?? []);
    }

    /**
     * @return array<string, string> name => source, relative to assets/, sorted by name
     */
    private static function registeredControllers(): array
    {
        $script = (string) file_get_contents(self::ROOT.'/assets/js/app.js');
        preg_match_all("~^import (\\w+) from '\\./([^']+)';~m", $script, $imports, \PREG_SET_ORDER);
        $sources = [];
        foreach ($imports as [, $class, $path]) {
            $sources[$class] = 'js/'.$path.'.js';
        }

        preg_match_all("~application\\.register\\('([^']+)', (\\w+)\\);~", $script, $registrations, \PREG_SET_ORDER);
        $controllers = [];
        foreach ($registrations as [, $name, $class]) {
            self::assertArrayHasKey($class, $sources, \sprintf('No import of %s in app.js.', $class));
            $controllers[$name] = $sources[$class];
        }
        ksort($controllers);

        return $controllers;
    }

    private static function packageName(): string
    {
        return '@'.self::json('composer.json')['name'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(string $path): array
    {
        $json = json_decode((string) file_get_contents(self::ROOT.'/'.$path), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($json);

        return $json;
    }
}
