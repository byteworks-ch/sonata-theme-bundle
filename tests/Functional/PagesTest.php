<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Every page of the test application renders with the theme: status 200 and no markup of Bootstrap 3, AdminLTE 2,
 * jQuery or inline scripts.
 */
final class PagesTest extends WebTestCase
{
    /**
     * Patterns of legacy markup (a class name as a whole word: Sonata's hook class "has-errors" is fine).
     */
    private const LEGACY_MARKUP = [
        '/data-toggle=/',
        '/data-dismiss=/',
        '/data-widget=/',
        '/[\s"]col-xs-\d/',
        '/[\s"]pull-(right|left)[\s"]/',
        '/[\s"]btn-default[\s"]/',
        '/[\s"]help-block[\s"]/',
        '/[\s"]has-error[\s"]/',
        '/[\s"]input-group-addon[\s"]/',
        '/class="box[\s"]/',
        '/[\s"](main-sidebar|content-wrapper|treeview-menu)[\s"]/',
        '/[\s"]label label-/',
        '#bundles/sonataadmin/app\.js#',
        '/admin-lte-skins/',
        '/\bjQuery\b/',
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function providePages(): iterable
    {
        yield 'dashboard' => ['/admin/dashboard'];
        yield 'search' => ['/admin/search?q=post'];
        foreach (['post', 'author', 'category', 'tag', 'page'] as $admin) {
            yield $admin.' list' => ['/admin/tests/app/'.$admin.'/list'];
            yield $admin.' create' => ['/admin/tests/app/'.$admin.'/create'];
            yield $admin.' edit' => ['/admin/tests/app/'.$admin.'/1/edit'];
            yield $admin.' show' => ['/admin/tests/app/'.$admin.'/1/show'];
            yield $admin.' delete' => ['/admin/tests/app/'.$admin.'/1/delete'];
        }
        yield 'post list with an active filter' => ['/admin/tests/app/post/list?filter[published][value]=1'];
        yield 'post list, page 2' => ['/admin/tests/app/post/list?filter[_page]=2'];
        yield 'page list with an autocomplete filter' => ['/admin/tests/app/page/list?filter[reviewer][value]=1'];
        yield 'post list as mosaic' => ['/admin/tests/app/post/list?_list_mode=mosaic'];
        yield 'post history' => ['/admin/tests/app/post/1/history'];
        yield 'page history' => ['/admin/tests/app/page/1/history'];
        yield 'choice of the subclass' => ['/admin/tests/app/media/create'];
        yield 'media create with a subclass' => ['/admin/tests/app/media/create?subclass=video'];
        yield 'media list' => ['/admin/tests/app/media/list'];
        yield 'custom action' => ['/admin/tests/app/page/1/summary'];
    }

    #[DataProvider('providePages')]
    public function testPage(string $url): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
        self::assertSelectorExists('.app-wrapper .app-sidebar .sidebar-menu');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideXhrPages(): iterable
    {
        yield 'list in the modal of an association field' => ['/admin/tests/app/category/list?select=1'];
        yield 'form in the modal of an association field' => ['/admin/tests/app/author/create'];
        yield 'revision next to the history' => ['/admin/tests/app/page/1/history/2/view'];
        yield 'comparison of two revisions' => ['/admin/tests/app/page/1/history/2/3/compare'];
    }

    #[DataProvider('provideXhrPages')]
    public function testXhrPage(string $url): void
    {
        $client = self::createClient();
        $client->xmlHttpRequest('GET', $url);

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        $this->assertThemeMarkup($content);
        self::assertStringNotContainsString('app-wrapper', $content);
    }

    public function testBatchConfirmation(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin/tests/app/post/list');
        // the batch action is a submit button named "action"
        $form = $crawler->filter('form[action*="/batch"] button[name="action"][value="delete"]')->form();
        $form['idx'] = ['1', '2'];
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.sonata-ba-delete .card-title', 'Confirm batch action');
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
    }

    public function testServerSideValidationMarksTheFieldAndTheTab(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin/tests/app/post/1/edit');
        $form = $crawler->selectButton('btn_update_and_edit')->form();
        $title = array_values(array_filter(array_keys($form->all()), static fn (string $name): bool => str_ends_with($name, '[title]')))[0];
        $form[$title] = '';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.sonata-ba-field-error input.is-invalid');
        self::assertSelectorExists('.sonata-ba-field-error-messages .invalid-feedback');
    }

    public function testInlineTabsMarkTheTabWithErrors(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin/tests/app/page/1/edit');
        $form = $crawler->selectButton('btn_update_and_edit')->form();
        $label = array_values(array_filter(array_keys($form->all()), static fn (string $name): bool => str_ends_with($name, '[links][1][label]')))[0];
        $form[$label] = '';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '.adminlte4-inline-item.border-danger');
        self::assertSelectorExists('.adminlte4-inline-item.border-danger .nav-link.active.text-danger .has-errors');
        self::assertSelectorExists('.adminlte4-inline-item.border-danger .sonata-ba-field-error input.is-invalid');
    }

    public function testDashboardBlocks(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(4, '.small-box[class*="text-bg-"] .small-box-icon');
        self::assertSelectorExists('.small-box.text-bg-info');
        self::assertSelectorTextContains('.card-title', 'Blog');
        self::assertSelectorExists('.card .sonata-ba-list');
        self::assertSelectorCount(3, '.sonata-feeds-container .list-group-item');
        self::assertSelectorExists('.accordion .accordion-collapse.show');
        self::assertAnySelectorTextContains('.card-title', 'Audit log');
    }

    public function testRevisionComparisonMarksTheDifference(): void
    {
        $client = self::createClient();
        $client->xmlHttpRequest('GET', '/admin/tests/app/page/1/history/2/3/compare');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.history-audit-compare th.diff');
    }

    public function testPreviewShowsTheObjectAndTheApproveButtons(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin/tests/app/page/1/edit');
        $client->submit($crawler->selectButton('btn_preview')->form());

        self::assertResponseIsSuccessful();
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
        self::assertSelectorExists('.sonata-ba-view .card .sonata-ba-view-table');
        self::assertSelectorExists('.sonata-preview-form-container button[name="btn_preview_approve"]');
        self::assertSelectorNotExists('button[name="btn_update_and_edit"]');
    }

    /**
     * Tabs of the edit and show pages as AdminLTE's demo shows them: the tab row and the panes in the body of an
     * outline card, the row with its own line; not in the card header.
     *
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideTabbedPages(): iterable
    {
        yield 'edit' => ['/admin/tests/app/post/2/edit', ['Content', 'Comments', 'Settings']];
        yield 'show' => ['/admin/tests/app/post/2/show', ['Content', 'Settings']];
    }

    /**
     * @param list<string> $tabs
     */
    #[DataProvider('provideTabbedPages')]
    public function testTabsInTheBodyOfAnOutlineCard(string $url, array $tabs): void
    {
        $page = self::createClient()->request('GET', $url);
        self::assertResponseIsSuccessful();

        $body = $page->filter('.card.card-outline.card-primary > .card-body')
            ->reduce(static fn (Crawler $body): bool => $body->filter(':scope > ul.nav-tabs[role="tablist"]')->count() > 0);
        self::assertCount(1, $body);
        self::assertSame($tabs, $body->filter(':scope > ul.nav-tabs .nav-link')->each(static fn (Crawler $tab): string => trim($tab->text())));
        self::assertCount(1, $body->filter(':scope > .tab-content'));
        self::assertCount(0, $page->filter('.card-header > .card-header-tabs, .tab-content.card-body'));
    }

    public function testTheHomePageLeadsToTheDashboard(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/admin/', 302);
        $client->followRedirect();
        self::assertResponseRedirects('/admin/dashboard');
    }

    public function testLoginLayout(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
        self::assertSelectorExists('body.login-page main.login-box .card .card-header .adminlte4-login-logo img');
        self::assertSelectorExists('.login-card-body form .input-group .input-group-text');
        self::assertSelectorExists('link[href*="bundles/byteworkssonatatheme/app.css"]');
        self::assertSelectorNotExists('.app-sidebar');
    }

    public function testEmptyLayoutHasNoHeaderAndNoSidebar(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin/tests/app/page/1/popup');

        self::assertResponseIsSuccessful();
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
        self::assertSelectorExists('main.app-main.adminlte4-empty-layout .card-title');
        self::assertSelectorNotExists('.app-sidebar');
        self::assertSelectorNotExists('.app-header');
        self::assertSelectorNotExists('.breadcrumb');
    }

    public function testAccessControlListOfAnObject(): void
    {
        $client = self::createClient(['environment' => 'acl'], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);
        $crawler = $client->request('GET', '/admin/tests/app/post/1/acl');

        self::assertResponseIsSuccessful();
        $this->assertThemeMarkup((string) $client->getResponse()->getContent());
        self::assertSelectorCount(2, 'table.adminlte4-acl-table');
        self::assertSelectorTextContains('table.adminlte4-acl-table', 'ROLE_EDITOR');
        self::assertSelectorExists('table.adminlte4-acl-table input.form-check-input[type="checkbox"]');

        // grant VIEW to ROLE_EDITOR and save
        $form = $crawler->filter('table.adminlte4-acl-table')->first()->closest('form')->form();
        $view = array_values(array_filter(array_keys($form->all()), static fn (string $name): bool => str_ends_with($name, '[VIEW]')))[0];
        $form[$view]->tick();
        $client->submit($form);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorExists('.alert-success');
    }

    private function assertThemeMarkup(string $content): void
    {
        foreach (self::LEGACY_MARKUP as $legacy) {
            self::assertDoesNotMatchRegularExpression($legacy, $content, \sprintf('Legacy markup %s found.', $legacy));
        }
        self::assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)[^>]*>#i', $content, 'Inline script found.');
    }
}
