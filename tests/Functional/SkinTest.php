<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The skin in the rendered pages: classes and data-bs-theme of header and sidebar, data-lte-primary, the palette's
 * stylesheet and the stylesheet of own colors. The test application takes the theme options from THEME_OPTIONS.
 */
final class SkinTest extends WebTestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['THEME_OPTIONS']);
        parent::tearDown();
    }

    public function testDefaultIsAdminLtesOwnLayout(): void
    {
        $page = $this->dashboard([]);

        self::assertNull($page->filter('html')->attr('data-lte-primary'));
        self::assertStringNotContainsString('compact-mode', (string) $page->filter('body')->attr('class'));
        self::assertSame(['app-header navbar navbar-expand bg-body', null], $this->region($page, '.app-header'));
        self::assertSame(['app-sidebar bg-body-secondary shadow', 'dark'], $this->region($page, '.app-sidebar'));
        self::assertSame(['bundles/byteworkssonatatheme/app.css', 'bundles/byteworkssonatatheme/colors.css', 'bundles/sonataform/app.css'], $this->stylesheets($page));
    }

    public function testPreset(): void
    {
        $page = $this->dashboard(['skin' => 'navy-sky']);

        self::assertSame('sky', $page->filter('html')->attr('data-lte-primary'));
        self::assertSame(['app-header navbar navbar-expand text-bg-sky', 'dark'], $this->region($page, '.app-header'));
        self::assertSame(['app-sidebar text-bg-navy shadow', 'dark'], $this->region($page, '.app-sidebar'));
    }

    public function testV3Palette(): void
    {
        $page = $this->dashboard(['skin' => 'blue', 'palette' => 'v3']);

        self::assertSame('lightblue', $page->filter('html')->attr('data-lte-primary'));
        self::assertSame(['app-header navbar navbar-expand text-bg-lightblue', 'dark'], $this->region($page, '.app-header'));
        self::assertContains('bundles/byteworkssonatatheme/colors-v3.css', $this->stylesheets($page));
        self::assertNotContains('bundles/byteworkssonatatheme/colors.css', $this->stylesheets($page));
    }

    public function testOwnPaletteBuildLoadsNoPalette(): void
    {
        $page = $this->dashboard(['palette' => 'none']);

        self::assertSame(['bundles/byteworkssonatatheme/app.css', 'bundles/sonataform/app.css'], $this->stylesheets($page));
    }

    public function testOwnColorsComeFromTheRouteOfTheBundle(): void
    {
        $client = $this->client([
            'colors' => ['brand' => '#0f766e'],
            'skins' => ['brand' => ['sidebar' => ['background' => 'brand'], 'primary' => 'brand']],
            'skin' => 'brand',
        ]);
        $page = $client->request('GET', '/admin/dashboard');
        self::assertResponseIsSuccessful();

        self::assertSame('brand', $page->filter('html')->attr('data-lte-primary'));
        self::assertSame(['app-sidebar text-bg-brand shadow', 'dark'], $this->region($page, '.app-sidebar'));
        $href = (string) $page->filter('link[rel="stylesheet"][href*="/custom-colors.css"]')->attr('href');
        self::assertMatchesRegularExpression('#^/bundles/byteworkssonatatheme/custom-colors\.css\?v=[0-9a-f]{12}$#', $href);

        // after the palette, so its data-lte-primary preset comes after the palette's defaults
        $links = $page->filter('link[rel="stylesheet"]')->each(static fn (Crawler $link): string => (string) $link->attr('href'));
        self::assertGreaterThan(array_search('/bundles/byteworkssonatatheme/colors.css', array_map(static fn (string $url): string => strtok($url, '?'), $links), true), array_search($href, $links, true));

        $client->request('GET', $href);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/css; charset=UTF-8');
        self::assertStringContainsString('immutable', (string) $client->getResponse()->headers->get('Cache-Control'));
        self::assertStringContainsString('--bs-brand:#0f766e;', (string) $client->getResponse()->getContent());

        // the login page has the same primary and stylesheet
        $login = $client->request('GET', '/login');
        self::assertSame('brand', $login->filter('html')->attr('data-lte-primary'));
        self::assertCount(1, $login->filter('link[href="'.$href.'"]'));
    }

    public function testCompactMode(): void
    {
        self::assertStringContainsString('compact-mode', (string) $this->dashboard(['layout' => ['compact' => true]])->filter('body')->attr('class'));
    }

    public function testBreadcrumbInTheContentHeaderByDefault(): void
    {
        $page = $this->client([])->request('GET', '/admin/tests/app/post/2/edit');
        self::assertResponseIsSuccessful();

        self::assertCount(1, $page->filter('.app-content-header .breadcrumb'));
        self::assertCount(0, $page->filter('.app-header .breadcrumb'));
    }

    public function testBreadcrumbInTheHeaderBar(): void
    {
        $page = $this->client(['layout' => ['breadcrumb' => 'header']])->request('GET', '/admin/tests/app/post/2/edit');
        self::assertResponseIsSuccessful();

        self::assertCount(1, $page->filter('.app-header .adminlte4-header-breadcrumb .breadcrumb'));
        // after the home icon: the admin's list and the object
        $items = $page->filter('.app-header .breadcrumb-item')->each(static fn (Crawler $item): string => trim($item->text()));
        self::assertSame(['Post List', 'Post number 2'], \array_slice($items, 1));
        self::assertCount(0, $page->filter('.app-content-header .breadcrumb'));
        self::assertStringContainsString('adminlte4-breadcrumb-in-header', (string) $page->filter('.app-content-header')->attr('class'));
    }

    public function testFullscreenToggleByDefaultAndOptional(): void
    {
        self::assertCount(1, $this->dashboard([])->filter('.app-header [data-lte-toggle="fullscreen"]'));
        self::ensureKernelShutdown();
        self::assertCount(0, $this->dashboard(['layout' => ['fullscreen' => false]])->filter('.app-header [data-lte-toggle="fullscreen"]'));
    }

    public function testTheDropdownsOfTheHeaderShowNoCaret(): void
    {
        // like AdminLTE's header: icons at equal distances, without the caret of .dropdown-toggle
        $header = $this->dashboard([])->filter('.app-header');

        // the color mode toggle and Sonata's "add" menu
        self::assertCount(2, $header->filter('[data-bs-toggle="dropdown"]'));
        self::assertCount(0, $header->filter('.dropdown-toggle'));
    }

    public function testWithoutOwnColorsTheRouteHasNoStylesheet(): void
    {
        $client = $this->client([]);
        $client->request('GET', '/bundles/byteworkssonatatheme/custom-colors.css');

        self::assertResponseStatusCodeSame(404);
    }

    public function testWrongSkinStopsTheApplication(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('byteworks_sonata_theme: The skin "blue" needs the palette "v3"');

        $this->client(['skin' => 'blue']);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function client(array $options): KernelBrowser
    {
        $_SERVER['THEME_OPTIONS'] = json_encode($options, \JSON_THROW_ON_ERROR);

        return self::createClient();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function dashboard(array $options): Crawler
    {
        $page = $this->client($options)->request('GET', '/admin/dashboard');
        self::assertResponseIsSuccessful();

        return $page;
    }

    /**
     * @return array{string, ?string} classes and data-bs-theme
     */
    private function region(Crawler $page, string $selector): array
    {
        $region = $page->filter($selector);

        return [(string) $region->attr('class'), $region->attr('data-bs-theme')];
    }

    /**
     * @return list<string> the stylesheets of the page, without the asset version
     */
    private function stylesheets(Crawler $page): array
    {
        return $page->filter('link[rel="stylesheet"]')->each(static fn (Crawler $link): string => ltrim((string) strtok((string) $link->attr('href'), '?'), '/'));
    }
}
