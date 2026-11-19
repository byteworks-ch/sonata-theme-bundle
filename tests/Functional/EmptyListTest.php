<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A list without results: the table with its header and one row over all columns that says why the list is empty and
 * what to do (reset the filters, create an entry, go to the first page). The box that selects all rows is disabled,
 * there is no footer.
 */
final class EmptyListTest extends WebTestCase
{
    public function testAFilterWithoutResultsOffersToResetTheFilters(): void
    {
        $cell = $this->emptyRow(self::createClient()->request('GET', '/admin/tests/app/tag/list?filter[name][value]=no-such-tag'));

        self::assertStringContainsString('No entries match these filters.', $cell->text());
        self::assertStringContainsString('filters=reset', (string) $cell->filter('a.btn')->attr('href'));
    }

    public function testAnEmptyListOffersToCreateAnEntry(): void
    {
        $client = self::createClient();
        // the same kernel and connection for the request, so the deletion is rolled back afterwards
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();
        try {
            $entityManager->createQuery('DELETE FROM '.Comment::class.' c')->execute();
            $cell = $this->emptyRow($client->request('GET', '/admin/tests/app/comment/list'));
        }
        finally {
            $entityManager->getConnection()->rollBack();
        }

        self::assertStringContainsString('No entries yet.', $cell->text());
        self::assertStringEndsWith('/admin/tests/app/comment/create', (string) $cell->filter('a.sonata-action-element')->attr('href'));
    }

    public function testAPageAfterTheLastOneLinksToTheFirstPage(): void
    {
        $cell = $this->emptyRow(self::createClient()->request('GET', '/admin/tests/app/post/list?filter[_page]=99'));

        self::assertStringContainsString('Go to the first page', $cell->text());
        self::assertStringContainsString('_page', urldecode((string) $cell->filter('a')->attr('href')));
    }

    /**
     * The only cell of the empty list, after checking the table around it.
     */
    private function emptyRow(Crawler $page): Crawler
    {
        self::assertResponseIsSuccessful();
        $table = $page->filter('table.sonata-ba-list.adminlte4-list-empty');
        self::assertCount(1, $table);

        // the header with its columns, and one cell over all of them
        $headers = $table->filter('thead > tr > th');
        self::assertGreaterThan(0, $headers->count());
        $cells = $table->filter('tbody > tr > td');
        self::assertCount(1, $cells);
        self::assertSame((string) $headers->count(), $cells->attr('colspan'));

        // nothing to select: the box that selects all rows stays in its place, disabled, so the columns do not move
        $all = $table->filter('thead #list_batch_checkbox');
        self::assertCount(1, $all);
        self::assertNotNull($all->attr('disabled'));

        // nothing to export or to page through
        self::assertCount(0, $table->ancestors()->filter('.card')->first()->filter('.card-footer'));

        return $cells;
    }
}
