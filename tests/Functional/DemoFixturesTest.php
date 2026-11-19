<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\Functional;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Comment;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Media;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Page;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The demo content (app:fixtures --demo) loads and the admin shows it. Afterwards the fixed data set of the tests is
 * loaded again, as the other tests expect it.
 */
final class DemoFixturesTest extends WebTestCase
{
    public function testTheAdminShowsTheDemoContent(): void
    {
        $client = self::createClient();
        try {
            $this->loadFixtures($client, ['--demo' => true]);

            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            \assert($entityManager instanceof EntityManagerInterface);
            self::assertSame(36, $entityManager->getRepository(Post::class)->count([]));
            self::assertSame(31, $entityManager->getRepository(Post::class)->count(['published' => true]));
            self::assertSame(7, $entityManager->getRepository(Page::class)->count([]));
            self::assertSame(12, $entityManager->getRepository(Media::class)->count([]));
            self::assertGreaterThan(30, $entityManager->getRepository(Comment::class)->count([]));

            // the newest published post first, in the list and in the dashboard's preview
            $client->request('GET', '/admin/dashboard');
            self::assertResponseIsSuccessful();
            self::assertAnySelectorTextContains('td', 'Our new hut directory is online');
            $client->request('GET', '/admin/tests/app/post/list?filter[published][value]=1');
            self::assertSelectorTextContains('tbody tr', 'Our new hut directory is online');

            $client->request('GET', '/admin/tests/app/post/list?filter[title][value]=quiet');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('table', 'Five quiet valleys for a summer weekend');

            foreach (['/admin/tests/app/post/1/show', '/admin/tests/app/post/1/edit', '/admin/tests/app/post/1/history',
                '/admin/tests/app/page/1/edit', '/admin/tests/app/page/1/history', '/admin/tests/app/media/list',
                '/admin/tests/app/author/list'] as $url) {
                $client->request('GET', $url);
                self::assertResponseIsSuccessful($url);
            }
            self::assertSelectorTextContains('table', 'Lena Brunner');
        }
        finally {
            $this->loadFixtures($client, []);
        }
    }

    /**
     * @param array<string, bool> $options
     */
    private function loadFixtures(KernelBrowser $client, array $options): void
    {
        $application = new Application($client->getKernel());
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $status = $application->run(new ArrayInput(['command' => 'app:fixtures', ...$options]), $output);
        self::assertSame(0, $status, $output->fetch());
    }
}
