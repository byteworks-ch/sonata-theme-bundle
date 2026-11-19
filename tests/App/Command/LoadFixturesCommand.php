<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Command;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Author;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Category;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Comment;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Image;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Page;
use Byteworks\SonataThemeBundle\Tests\App\Entity\PageLink;
use Byteworks\SonataThemeBundle\Tests\App\Entity\PageSeo;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Post;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Tag;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Video;
use Byteworks\SonataThemeBundle\Tests\App\Fixtures\DemoFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Recreates the schema of the test application and loads a fixed data set, which the tests rely on; with --demo the
 * demo content instead.
 */
#[AsCommand(name: 'app:fixtures', description: 'Recreate the schema and load the fixtures of the test application')]
final class LoadFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('demo', null, InputOption::VALUE_NONE, 'Load the demo content (an outdoor magazine) instead of the data set of the tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        if ($input->getOption('demo')) {
            (new DemoFixtures($this->entityManager))->load();
            $output->writeln('Demo content loaded.');

            return Command::SUCCESS;
        }

        $categories = [];
        foreach (['News', 'Release notes', 'Tutorials', 'Events'] as $name) {
            $categories[] = $category = new Category();
            $category->name = $name;
            $this->entityManager->persist($category);
        }

        $tags = [];
        foreach (['symfony', 'sonata', 'adminlte', 'bootstrap', 'php'] as $name) {
            $tags[] = $tag = new Tag();
            $tag->name = $name;
            $this->entityManager->persist($tag);
        }

        $authors = [];
        foreach ([['Ada Lovelace', 'ada@example.org'], ['Alan Turing', 'alan@example.org'], ['Grace Hopper', 'grace@example.org']] as $index => [$name, $email]) {
            $authors[] = $author = new Author();
            $author->name = $name;
            $author->email = $email;
            $author->category = $categories[$index];
            $this->entityManager->persist($author);
        }

        for ($i = 1; $i <= 32; ++$i) {
            $post = new Post();
            $post->title = \sprintf('Post number %d', $i);
            $post->body = \sprintf("Body of post %d.\nSecond line.", $i);
            $post->published = 0 === $i % 3;
            $post->publishedAt = $post->published ? new \DateTimeImmutable(\sprintf('2026-%02d-%02d 10:00:00', 1 + $i % 12, 1 + $i % 28)) : null;
            $post->category = $categories[$i % \count($categories)];
            $post->author = $authors[$i % \count($authors)];
            $post->tags->add($tags[$i % \count($tags)]);
            if ($i <= 3) {
                for ($j = 1; $j <= 2; ++$j) {
                    $comment = new Comment();
                    $comment->author = \sprintf('Reader %d', $j);
                    $comment->message = \sprintf('Comment %d on post %d', $j, $i);
                    $comment->approved = 1 === $j;
                    $comment->position = $j;
                    $post->addComment($comment);
                }
            }
            $this->entityManager->persist($post);
        }

        $this->entityManager->flush();

        $posts = $this->entityManager->getRepository(Post::class)->findBy([], ['id' => 'ASC'], 2);

        $about = new Page();
        $about->title = 'About us';
        $about->body = 'Who we are.';
        $about->reviewer = $authors[0];
        $about->sections = ['intro', 'gallery', 'footer'];
        $about->featuredTag = $tags[0];
        $about->seo = new PageSeo();
        $about->seo->metaTitle = 'About us | Example';
        $about->seo->metaDescription = 'The people behind the example.';
        foreach ($posts as $post) {
            $about->related->add($post);
        }
        foreach ([['Home', 'https://example.org/'], ['Documentation', 'https://example.org/docs'], ['Contact', 'https://example.org/contact']] as $index => [$label, $url]) {
            $link = new PageLink();
            $link->label = $label;
            $link->url = $url;
            $link->position = $index + 1;
            $about->addLink($link);
        }
        $about->labels->add($tags[1]);
        $this->entityManager->persist($about);

        $docs = new Page();
        $docs->title = 'External documentation';
        $docs->format = 'link';
        $docs->url = 'https://docs.example.org/';
        $this->entityManager->persist($docs);

        $image = new Image();
        $image->name = 'Team photo';
        $image->width = 1600;
        $image->height = 900;
        $this->entityManager->persist($image);
        $video = new Video();
        $video->name = 'Product tour';
        $video->duration = 95;
        $this->entityManager->persist($video);

        $this->entityManager->flush();

        // a second revision for the history
        $about->body = "Who we are.\nAnd what we do.";
        $this->entityManager->flush();

        $output->writeln('Fixtures loaded.');

        return Command::SUCCESS;
    }
}
