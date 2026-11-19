<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Icons of the sidebar's menu items, as Sonata sets them from the attribute "item_icon" of an admin's tag (the test
 * application sets CSS classes for most admins and HTML for the media).
 */
final class SidebarIconsTest extends WebTestCase
{
    public function testMenuItemsShowTheIconsOfTheirAdmins(): void
    {
        $page = $this->dashboard();

        self::assertCount(1, $this->menuLink($page, '/post/list')->filter('i.fas.fa-pen-to-square.nav-icon'));
        self::assertCount(1, $this->menuLink($page, '/author/list')->filter('i.fas.fa-user-pen.nav-icon'));
    }

    public function testAnIconAsHtmlKeepsItsMarkup(): void
    {
        $page = $this->dashboard();

        self::assertCount(1, $this->menuLink($page, '/media/list')->filter('span.nav-icon > i.fas.fa-photo-film'));
    }

    private function dashboard(): Crawler
    {
        $page = self::createClient()->request('GET', '/admin/dashboard');
        self::assertResponseIsSuccessful();

        return $page;
    }

    private function menuLink(Crawler $page, string $path): Crawler
    {
        $link = $page->filter(\sprintf('.app-sidebar .sidebar-menu a.nav-link[href$="%s"]', $path));
        self::assertCount(1, $link, $path);

        return $link;
    }
}
