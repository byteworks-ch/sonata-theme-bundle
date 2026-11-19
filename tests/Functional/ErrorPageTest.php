<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\ErrorRenderer\TwigErrorRenderer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Symfony's error pages on the theme's error page (the test app replaces them as a project does): the status code,
 * a title and a sentence for it, the dashboard link, the theme's assets; never details of the error. Rendered as in
 * production (the test environment has debug on, which shows Symfony's exception page instead).
 */
final class ErrorPageTest extends WebTestCase
{
    /**
     * @return iterable<string, array{\Throwable, int, string}>
     */
    public static function errors(): iterable
    {
        yield 'not found' => [new NotFoundHttpException('No route found for "GET /secret-path"'), 404, 'Page not found'];
        yield 'access denied' => [new AccessDeniedHttpException('Access Denied by #[IsGranted("ROLE_SECRET")]'), 403, 'Access denied'];
        yield 'server error' => [new \RuntimeException('SQLSTATE secret detail'), 500, 'Something went wrong'];
        yield 'server error without texts of its own' => [new HttpException(502, 'secret detail'), 502, 'Something went wrong'];
        yield 'client error without texts of its own' => [new HttpException(418, 'secret detail'), 418, 'Request not possible'];
    }

    #[DataProvider('errors')]
    public function testTheErrorPageShowsTheStatusWithoutDetails(\Throwable $error, int $code, string $title): void
    {
        $html = $this->render($error);
        $page = new Crawler($html);

        self::assertSame($code.'', trim($page->filter('main.adminlte4-error-page .display-1.text-primary')->text()));
        self::assertSame($title, trim($page->filter('main.adminlte4-error-page h1')->text()));
        self::assertSame($title.' ('.$code.')', trim($page->filter('title')->text()));
        self::assertNotSame('', trim($page->filter('main.adminlte4-error-page p.text-body-secondary')->text()));
        self::assertSame('/admin/dashboard', $page->filter('main a.btn-primary')->attr('href'));
        // "back" stays hidden until its controller finds a previous page of the application
        $back = $page->filter('main button.btn-outline-secondary[hidden][data-controller="adminlte4-back"]');
        self::assertCount(1, $back);
        self::assertSame('click->adminlte4-back#back', $back->attr('data-action'));
        self::assertCount(1, $page->filter('main .adminlte4-login-logo'));
        self::assertCount(1, $page->filter('link[href*="bundles/byteworkssonatatheme/app.css"]'));
        self::assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)[^>]*>#i', $html, 'Inline script found.');
        self::assertStringNotContainsString('secret', $html);
        self::assertStringNotContainsString('ROLE_SECRET', $html);
    }

    public function testTheTextsFollowTheLocale(): void
    {
        self::getContainer()->get('translator')->setLocale('de');

        $page = new Crawler($this->render(new NotFoundHttpException()));

        self::assertSame('Seite nicht gefunden', trim($page->filter('main h1')->text()));
        self::assertSame('Zur Startseite', trim($page->filter('main a.btn-primary')->text()));
        self::assertSame('Zurück', trim($page->filter('main button')->text()));
    }

    public function testSymfonysPreviewShowsTheErrorPage(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/_error/404');

        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextSame('main.adminlte4-error-page h1', 'Page not found');
    }

    private function render(\Throwable $error): string
    {
        self::getContainer()->get('request_stack')->push(Request::create('/admin/somewhere'));

        return (new TwigErrorRenderer(self::getContainer()->get('twig'), null, false))->render($error)->getAsString();
    }
}
