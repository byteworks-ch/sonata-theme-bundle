<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Fixtures;

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
use Doctrine\ORM\EntityManagerInterface;

/**
 * Demo content for showing the theme: an outdoor magazine with posts, comments, pages and media. The texts are fixed;
 * the dates count back from the day of loading, so the demo always looks current. The tests use the fixed data set
 * of the command app:fixtures instead.
 */
final class DemoFixtures
{
    private const CATEGORIES = ['Destinations', 'Guides', 'Gear', 'Events', 'News'];

    private const TAGS = [
        'hiking', 'skiing', 'cycling', 'climbing', 'family', 'winter',
        'summer', 'mountain huts', 'lakes', 'photography', 'sustainability', 'beginners',
    ];

    /** name, email, category */
    private const AUTHORS = [
        ['Lena Brunner', 'lena.brunner@example.org', 'Destinations'],
        ['Marco Rossi', 'marco.rossi@example.org', 'Gear'],
        ['Sophie Keller', 'sophie.keller@example.org', 'Guides'],
        ['Jonas Meier', 'jonas.meier@example.org', 'Events'],
        ['Elena Costa', 'elena.costa@example.org', 'News'],
        ['Noah Weber', 'noah.weber@example.org', 'Destinations'],
    ];

    /** title, category, tags, days since publication (null: a draft), lead */
    private const POSTS = [
        ['Five quiet valleys for a summer weekend', 'Destinations', ['hiking', 'summer'], 3,
            'Crowded trails are not the only option in July. These five valleys are easy to reach by train and still feel remote.'],
        ['Along the shores of Lake Sils in autumn', 'Destinations', ['lakes', 'photography'], 11,
            'When the larches turn golden, the path around Lake Sils becomes one of the most photographed walks in the Alps.'],
        ['A week of hut-to-hut hiking in the Bernese Oberland', 'Destinations', ['hiking', 'mountain huts'], 19,
            'Six huts, five passes and one unforgettable sunrise: our route for a first long-distance hike.'],
        ['Winter in the Engadin: beyond the ski slopes', 'Destinations', ['winter', 'skiing'], 27,
            'Cross-country trails, frozen lakes and winter hiking paths make the Engadin worth a visit even without skis.'],
        ['Cycling the Rhône from the glacier to Lake Geneva', 'Destinations', ['cycling', 'lakes'], 41,
            'Mostly downhill, always along the river: a relaxed four-day bike tour through the Valais.'],
        ['The best viewpoints within an hour of Lucerne', 'Destinations', ['hiking', 'photography', 'family'], 55,
            'Short walks with big views, all of them reachable by boat, train or cable car.'],
        ['Ticino in spring: chestnut forests and stone villages', 'Destinations', ['hiking', 'photography'], 72,
            'While the north is still waiting for spring, the valleys of Ticino are already green.'],
        ['Hidden lakes of the Valais', 'Destinations', ['lakes', 'hiking'], null,
            'Seven mountain lakes that most visitors pass by without knowing.'],
        ['How to plan your first multi-day hike', 'Guides', ['hiking', 'beginners'], 6,
            'Route, huts, packing list and a plan B: everything you need to decide before you set off.'],
        ['Reading avalanche bulletins: a short introduction', 'Guides', ['winter', 'skiing'], 23,
            'Danger levels, avalanche problems and the core zone explained in plain words.'],
        ['Hiking with kids: ten tips that actually work', 'Guides', ['family', 'hiking', 'beginners'], 34,
            'Short stages, a goal worth reaching and enough snacks: our tips from many family outings.'],
        ['Layering for cold days, explained', 'Guides', ['winter', 'beginners'], 48,
            'Base layer, mid layer, shell: how to stay warm without overheating on the way up.'],
        ['Booking mountain huts: what you need to know', 'Guides', ['mountain huts'], 63,
            'When to book, what half board includes and why you should always cancel if your plans change.'],
        ['Taking better photos at sunrise', 'Guides', ['photography'], 85,
            'Blue hour, golden hour and a cold thermos of tea: how to make the most of the early start.'],
        ['Leave no trace: how to keep the trails clean', 'Guides', ['sustainability'], 101,
            'Seven simple habits that help keep the mountains the way we found them.'],
        ['First steps on via ferratas', 'Guides', ['climbing', 'beginners'], null,
            'Which equipment you need, how to choose a route and where to practice safely.'],
        ['Tested: six lightweight rain jackets', 'Gear', ['hiking'], 9,
            'We wore them through two wet weeks in June. Here is how they held up.'],
        ['Choosing touring skis for your first season', 'Gear', ['skiing', 'beginners'], 31,
            'Width, weight and bindings: what matters when you start ski touring.'],
        ['Repair instead of replace: caring for your outdoor gear', 'Gear', ['sustainability'], 46,
            'A torn jacket or a worn sole is not the end. Most gear lasts years longer with a little care.'],
        ['The camera bag that survived a whole summer', 'Gear', ['photography'], 67,
            'Rain, dust and a fall on a scree slope: a long-term report.'],
        ['E-bikes in the mountains: what to look for', 'Gear', ['cycling'], 93,
            'Battery range, motor support and brakes that can handle long descents.'],
        ['Our long-term test of three hiking boots', 'Gear', ['hiking'], 120,
            'Eight hundred kilometres later we know which boots we would buy again.'],
        ['Child carriers compared', 'Gear', ['family'], null,
            'Comfort for parents, safety for children: five carriers on the same route.'],
        ['Join our autumn photo walk', 'Events', ['photography'], 2,
            'Walk with our photographers through the colours of autumn and learn a few tricks on the way.'],
        ['Trail running weekend in Davos: registrations open', 'Events', ['summer'], 15,
            'Three distances, one festival: the trail running weekend is back for its fourth edition.'],
        ['Avalanche course for beginners in January', 'Events', ['winter', 'beginners'], 38,
            'Two days in the snow with certified mountain guides, from transceiver search to route planning.'],
        ['Clean-up day on the trails above Zurich', 'Events', ['sustainability', 'family'], 59,
            'Bring gloves, we bring the bags and a warm lunch for everyone who helps.'],
        ['Film evening: stories from the high Alps', 'Events', ['photography'], 81,
            'Four short films about people who live and work above two thousand metres.'],
        ['Family hiking day with picnic', 'Events', ['family'], null,
            'An easy loop for all ages, a picnic in the meadow and games for the children.'],
        ['Our new hut directory is online', 'News', ['mountain huts'], 1,
            'More than three hundred huts with opening times, access routes and booking links.'],
        ['Ten years of the magazine: thank you!', 'News', [], 26,
            'What started as a small newsletter has become a magazine with readers from all over Europe.'],
        ['New train connection makes the Valais easier to reach', 'News', ['sustainability'], 52,
            'From December, a direct connection saves almost an hour on the way to the side valleys.'],
        ['Photo contest: the winners', 'News', ['photography'], 77,
            'Out of more than two thousand entries, the jury chose these twelve pictures.'],
        ['Survey results: how our readers travel to the mountains', 'News', ['sustainability'], 110,
            'More than half of you come by train. Here are all the results of our reader survey.'],
        ['Trail closures after the summer storms', 'News', ['hiking'], 140,
            'Several paths are closed after landslides. We keep this list up to date.'],
        ['Newsletter now every two weeks', 'News', [], null,
            'From next month, our newsletter arrives every second Thursday.'],
    ];

    /** a second paragraph per category, chosen in turn */
    private const PARAGRAPHS = [
        'Destinations' => [
            'The best time to go is early in the morning, before the first cable car arrives. Public transport takes you to the start of every route, and most of them end near a village with a café.',
            'Accommodation fills up quickly in high season, so book a few weeks ahead. If you prefer quiet, come in June or September: the weather is often stable and the trails are empty.',
        ],
        'Guides' => [
            'Start with a short tour and increase the distance step by step. Check the weather forecast the evening before and again in the morning, and always tell someone where you are going.',
            'None of this replaces experience. If you are unsure, go with someone who knows the area, or book a course with a certified guide.',
        ],
        'Gear' => [
            'We tested everything on real tours, not in the lab: in rain, in heat and on long days with a heavy pack. None of the manufacturers paid for this review.',
            'Good equipment does not have to be new. Rental shops and second-hand markets are a great way to try things out before you buy.',
        ],
        'Events' => [
            'Places are limited, so register early. Participation is free for members of the magazine club; everyone else pays a small fee that goes to the local mountain rescue.',
            'The event takes place in any weather. If conditions are dangerous, we move to an alternative route and let all participants know the evening before.',
        ],
        'News' => [
            'Thank you for all your messages and suggestions. Many of the changes we make start with an email from one of our readers.',
            'Questions or feedback? Write to us at hello@example.org; we read every message.',
        ],
    ];

    private const READERS = ['Anna', 'Thomas K.', 'Martina', 'Lukas', 'Sarah W.', 'Peter', 'Claudia', 'Reto', 'Nina', 'David'];

    private const COMMENTS = [
        'Thanks for the tips! We did this last weekend and it was wonderful.',
        'Great article. Is the route also suitable for dogs?',
        'The last part is steeper than described, take your time.',
        'We went in October and had the trail almost to ourselves.',
        'Very helpful, I printed it for our next trip.',
        'Beautiful pictures, which camera do you use?',
        'Is there a version of this for families with small children?',
        'The train connection on Sundays is only every two hours, plan accordingly.',
        'I would add a headlamp to the list, we needed ours on the way down.',
        'Lovely read. More articles like this, please!',
        'The hut was fully booked in August, so reserve early.',
        'Spam: cheap watches at example.com',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function load(): void
    {
        $today = new \DateTimeImmutable('today');

        $categories = [];
        foreach (self::CATEGORIES as $name) {
            $categories[$name] = $category = new Category();
            $category->name = $name;
            $this->entityManager->persist($category);
        }

        $tags = [];
        foreach (self::TAGS as $name) {
            $tags[$name] = $tag = new Tag();
            $tag->name = $name;
            $this->entityManager->persist($tag);
        }

        $authors = [];
        foreach (self::AUTHORS as [$name, $email, $category]) {
            $authors[] = $author = new Author();
            $author->name = $name;
            $author->email = $email;
            $author->category = $categories[$category];
            $this->entityManager->persist($author);
        }

        // newest first: the lists show the posts in the order of their ids (drafts, then by date of publication)
        $entries = self::POSTS;
        usort($entries, static fn (array $a, array $b): int => [null !== $a[3], $a[3]] <=> [null !== $b[3], $b[3]]);

        $posts = [];
        $paragraph = array_fill_keys(self::CATEGORIES, 0);
        foreach ($entries as $index => [$title, $category, $postTags, $days, $lead]) {
            $posts[$title] = $post = new Post();
            $post->title = $title;
            $post->body = $lead."\n\n".self::PARAGRAPHS[$category][$paragraph[$category]++ % 2];
            $post->category = $categories[$category];
            $post->author = $authors[$index % \count($authors)];
            $post->published = null !== $days;
            $post->publishedAt = null === $days ? null : $today->modify(\sprintf('-%d days', $days))->setTime(7 + $index % 4, 15 * ($index % 4));
            foreach ($postTags as $name) {
                $post->tags->add($tags[$name]);
            }
            // comments on the published posts: none to four, one of them waiting for approval now and then
            for ($j = 0; $post->published && $j < ($index * 7) % 5; ++$j) {
                $comment = new Comment();
                $comment->author = self::READERS[($index + $j * 3) % \count(self::READERS)];
                $comment->message = self::COMMENTS[($index * 5 + $j) % (\count(self::COMMENTS) - 1)];
                $comment->approved = 0 !== ($index + $j) % 6;
                $comment->visibility = 3 === $j ? 'internal' : 'public';
                $comment->position = $j + 1;
                $post->addComment($comment);
            }
            $this->entityManager->persist($post);
        }
        // one comment that a moderator still has to remove
        $quiet = $posts['Five quiet valleys for a summer weekend'];
        $spam = new Comment();
        $spam->author = 'Visitor';
        $spam->message = self::COMMENTS[\count(self::COMMENTS) - 1];
        $spam->position = 1 + $quiet->getComments()->count();
        $quiet->addComment($spam);

        $pages = $this->pages($authors, $tags, $posts);
        $this->media();

        $this->entityManager->flush();

        // second revisions for the history and the audit block of the dashboard
        $quiet->body .= "\n\nUpdate: the bus to the start of the second route now also runs on weekdays.";
        $posts['How to plan your first multi-day hike']->body .= "\n\nUpdate: we added a checklist for the first aid kit.";
        $pages[0]->body .= "\n\nWe are always looking for new authors: see \"Write for us\".";
        $this->entityManager->flush();
    }

    /**
     * @param list<Author>          $authors
     * @param array<string, Tag>    $tags
     * @param array<string, Post>   $posts by title
     *
     * @return list<Page>
     */
    private function pages(array $authors, array $tags, array $posts): array
    {
        $about = $this->page('About us', "We are a small team of hikers, skiers and photographers who write about the mountains we love.\nOur articles are independent: nobody pays to be featured.", $authors[0]);
        $about->sections = ['header', 'intro', 'gallery', 'footer'];
        $about->featuredTag = $tags['hiking'];
        $about->seo = $this->seo('About us | Outdoor Magazine', 'The team behind the magazine and how we work.');
        foreach (['Five quiet valleys for a summer weekend', 'A week of hut-to-hut hiking in the Bernese Oberland', 'Ten years of the magazine: thank you!'] as $title) {
            $about->related->add($posts[$title]);
        }
        $this->links($about, [['Our team', 'https://example.org/team', null], ['Newsletter', 'https://example.org/newsletter', 'Every second Thursday'], ['Contact', 'https://example.org/contact', null]]);
        $about->labels->add($tags['photography']);
        $about->labels->add($tags['sustainability']);

        $contact = $this->page('Contact', "Write to us at hello@example.org.\nWe answer within two working days.", $authors[4]);
        $contact->sections = ['header', 'footer'];
        $contact->seo = $this->seo('Contact | Outdoor Magazine', 'How to reach the editorial team.');
        $this->links($contact, [['Email', 'mailto:hello@example.org', null], ['Map', 'https://example.org/map', 'Our office is two minutes from the station']]);

        $write = $this->page('Write for us', "Do you have a story from the mountains? We publish guest articles every month.\nSend us a short outline and two or three photos.", $authors[2]);
        $write->sections = ['intro', 'comments'];
        $write->featuredTag = $tags['beginners'];
        $write->related->add($posts['How to plan your first multi-day hike']);

        $this->page('Imprint', "Outdoor Magazine\nExample Street 1\n8000 Zurich", null);
        $this->page('Privacy policy', "We only collect the data we need to send the newsletter.\nYou can unsubscribe at any time.", $authors[4]);

        $newsletter = $this->page('Newsletter', null, null);
        $newsletter->format = 'link';
        $newsletter->url = 'https://example.org/newsletter';

        $shop = $this->page('Shop', null, null);
        $shop->format = 'link';
        $shop->url = 'https://shop.example.org/';

        return [$about, $contact, $write];
    }

    private function page(string $title, ?string $body, ?Author $reviewer): Page
    {
        $page = new Page();
        $page->title = $title;
        $page->body = $body;
        $page->reviewer = $reviewer;
        $this->entityManager->persist($page);

        return $page;
    }

    private function seo(string $title, string $description): PageSeo
    {
        $seo = new PageSeo();
        $seo->metaTitle = $title;
        $seo->metaDescription = $description;

        return $seo;
    }

    /**
     * @param list<array{string, string, ?string}> $links label, URL, note
     */
    private function links(Page $page, array $links): void
    {
        foreach ($links as $index => [$label, $url, $note]) {
            $link = new PageLink();
            $link->label = $label;
            $link->url = $url;
            $link->note = $note;
            $link->position = $index + 1;
            $page->addLink($link);
        }
    }

    private function media(): void
    {
        foreach ([
            ['Sunrise above the Aletsch Glacier', 4000, 2667],
            ['Larches at Lake Sils', 3000, 2000],
            ['Hut terrace at dusk', 2400, 1600],
            ['Cyclists along the Rhône', 3200, 1800],
            ['Stone village in Ticino', 2048, 1365],
            ['Rain jacket test, day four', 1600, 1067],
            ['Readers at the photo walk', 2400, 1350],
            ['Team portrait', 1800, 1200],
        ] as [$name, $width, $height]) {
            $image = new Image();
            $image->name = $name;
            $image->width = $width;
            $image->height = $height;
            $this->entityManager->persist($image);
        }

        foreach ([['Packing for a hut tour', 214], ['Avalanche transceiver search in five minutes', 312], ['Trail running weekend: highlights', 145], ['Ten years of the magazine', 96]] as [$name, $duration]) {
            $video = new Video();
            $video->name = $name;
            $video->duration = $duration;
            $this->entityManager->persist($video);
        }
    }
}
