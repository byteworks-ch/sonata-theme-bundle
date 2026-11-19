<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App;

use Byteworks\SonataThemeBundle\ByteworksSonataThemeBundle;
use Byteworks\SonataThemeBundle\Tests\App\Admin\AuthorAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\CategoryAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\CommentAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\MediaAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\PageAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\PageLinkAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\PageSeoAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\PostAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Admin\TagAdmin;
use Byteworks\SonataThemeBundle\Tests\App\Command\LoadFixturesCommand;
use Byteworks\SonataThemeBundle\Tests\App\Controller\PageAdminController;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Author;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Category;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Comment;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Image;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Media;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Page;
use Byteworks\SonataThemeBundle\Tests\App\Entity\PageLink;
use Byteworks\SonataThemeBundle\Tests\App\Entity\PageSeo;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Post;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Tag;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Video;
use Byteworks\SonataThemeBundle\Tests\App\Security\AclUserManager;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\BlockBundle\SonataBlockBundle;
use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle;
use Sonata\Exporter\Bridge\Symfony\SonataExporterBundle;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Sonata\Form\Bridge\Symfony\SonataFormBundle;
use Sonata\Twig\Bridge\Symfony\SonataTwigBundle;
use Symfony\Bundle\AclBundle\AclBundle;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\UX\StimulusBundle\StimulusBundle;

/**
 * Test application of the theme: Sonata admin with Doctrine ORM on SQLite and admins for the features the theme
 * covers. The environment "original" leaves the theme out and shows Sonata as it ships, for comparison; the
 * environment "acl" uses access control lists and needs a login (HTTP basic, users "admin" and "editor", password
 * as the name).
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SecurityBundle();
        yield new DoctrineBundle();
        yield new KnpMenuBundle();
        yield new StimulusBundle();
        yield new SonataDoctrineBundle();
        yield new SonataTwigBundle();
        yield new SonataFormBundle();
        yield new SonataBlockBundle();
        yield new SonataAdminBundle();
        yield new SonataDoctrineORMAdminBundle();
        yield new SonataExporterBundle();
        yield new SimpleThingsEntityAuditBundle();
        if ($this->isAcl()) {
            yield new AclBundle();
        }
        if (!$this->isOriginal()) {
            yield new ByteworksSonataThemeBundle();
        }
    }

    private function isOriginal(): bool
    {
        return 'original' === $this->environment;
    }

    private function isAcl(): bool
    {
        return 'acl' === $this->environment;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        $options = $this->themeOptions();

        return __DIR__.'/var/cache/'.$this->environment.([] === $options ? '' : '-'.md5(serialize($options)));
    }

    /**
     * Theme options from the environment variable THEME_OPTIONS (JSON), to look at variants without editing this
     * file; each variant gets its own container cache.
     *
     * @return array<string, mixed>
     */
    private function themeOptions(): array
    {
        $json = $_SERVER['THEME_OPTIONS'] ?? getenv('THEME_OPTIONS') ?: '';

        return '' === $json ? [] : json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function getLogDir(): string
    {
        return __DIR__.'/var/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'sonata-theme-test',
            // "acl" for the functional tests of access control lists as well
            'test' => \in_array($this->environment, ['test', 'acl'], true),
            'http_method_override' => true,
            'session' => [
                'storage_factory_id' => \in_array($this->environment, ['test', 'acl'], true) ? 'session.storage.factory.mock_file' : 'session.storage.factory.native',
            ],
            'csrf_protection' => true,
            'form' => true,
            'validation' => true,
            'property_access' => true,
            'assets' => [],
            'default_locale' => 'en',
            'translator' => ['fallbacks' => ['en']],
            'router' => ['utf8' => true],
            'profiler' => ['enabled' => 'test' === $this->environment, 'collect' => false],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'strict_variables' => true,
        ]);

        if ($this->isAcl()) {
            $container->extension('security', [
                'password_hashers' => [InMemoryUser::class => 'plaintext'],
                'providers' => ['in_memory' => ['memory' => ['users' => [
                    'admin' => ['password' => 'admin', 'roles' => ['ROLE_SUPER_ADMIN']],
                    'editor' => ['password' => 'editor', 'roles' => ['ROLE_EDITOR']],
                ]]]],
                'role_hierarchy' => ['ROLE_EDITOR' => ['ROLE_ADMIN'], 'ROLE_SUPER_ADMIN' => ['ROLE_EDITOR']],
                'firewalls' => ['main' => ['lazy' => true, 'provider' => 'in_memory', 'http_basic' => ['realm' => 'Test app']]],
                'access_control' => [['path' => '^/admin', 'roles' => 'ROLE_ADMIN']],
            ]);
            $container->extension('acl', ['connection' => 'default']);
        } else {
            $container->extension('security', [
                'providers' => ['in_memory' => ['memory' => []]],
                'firewalls' => ['main' => ['lazy' => true, 'provider' => 'in_memory']],
            ]);
        }

        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db'],
            'orm' => [
                'mappings' => [
                    'App' => [
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/Entity',
                        'prefix' => 'Byteworks\SonataThemeBundle\Tests\App\Entity',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);

        $container->extension('sonata_admin', [
            'title' => 'AdminLTE 4 Theme',
            // the theme's layout with a fullscreen switch in the header
            'templates' => ['layout' => 'admin/layout.html.twig'],
            'options' => ['default_translation_domain' => 'messages'],
            'security' => $this->isAcl()
                ? ['handler' => 'sonata.admin.security.handler.acl', 'acl_user_manager' => AclUserManager::class]
                : [],
            'dashboard' => [
                'groups' => [
                    'blog' => ['label' => 'Blog', 'icon' => 'fas fa-newspaper'],
                    'taxonomy' => ['label' => 'Taxonomy', 'icon' => 'fas fa-tags'],
                    'content' => ['label' => 'Content', 'icon' => 'fas fa-file-lines'],
                ],
                // every kind of dashboard block; the colors as AdminLTE 2 named them
                'blocks' => [
                    ...array_map(static fn (array $stats): array => [
                        'type' => 'sonata.admin.block.stats',
                        'position' => 'top',
                        'class' => 'col-6 col-lg-3',
                        'settings' => $stats,
                    ], [
                        ['code' => PostAdmin::class, 'icon' => 'fas fa-newspaper', 'text' => 'Posts', 'color' => 'bg-aqua'],
                        ['code' => PostAdmin::class, 'icon' => 'fas fa-check', 'text' => 'Published', 'color' => 'bg-green', 'filters' => ['published' => ['value' => 1]]],
                        ['code' => PageAdmin::class, 'icon' => 'fas fa-file-lines', 'text' => 'Pages', 'color' => 'bg-yellow'],
                        ['code' => MediaAdmin::class, 'icon' => 'fas fa-photo-film', 'text' => 'Media', 'color' => 'bg-red'],
                    ]),
                    ['type' => 'sonata.admin.block.admin_list', 'position' => 'left', 'class' => 'col-lg-6'],
                    ['type' => 'sonata.admin.block.admin_preview', 'position' => 'right', 'class' => 'col-lg-6', 'settings' => [
                        'code' => PostAdmin::class,
                        'icon' => 'fas fa-newspaper',
                        'text' => 'Latest published posts',
                        'limit' => 3,
                        'filters' => ['published' => ['value' => 1]],
                        'remove_list_fields' => [ListMapper::NAME_ACTIONS],
                    ]],
                    ['type' => 'sonata.block.service.rss', 'position' => 'right', 'class' => 'col-lg-6', 'settings' => [
                        'title' => 'Project news',
                        'url' => '%kernel.project_dir%/feed.xml',
                        'template' => '@SonataAdmin/Block/block_rss_dashboard.html.twig',
                    ]],
                    ['type' => 'sonata.admin_doctrine_orm.block.audit', 'position' => 'right', 'class' => 'col-lg-6', 'settings' => ['limit' => 3]],
                    ['type' => 'sonata.block.service.text', 'position' => 'bottom', 'class' => 'col-lg-4', 'settings' => [
                        'content' => '<div class="card mb-3"><div class="card-body">A text block with <strong>markup</strong>.</div></div>',
                    ]],
                    ['type' => 'sonata.block.service.rss', 'position' => 'bottom', 'class' => 'col-lg-4', 'settings' => [
                        'title' => 'Project news',
                        'url' => '%kernel.project_dir%/feed.xml',
                        'icon' => 'fas fa-rss',
                    ]],
                    ['type' => 'sonata.block.service.menu', 'position' => 'bottom', 'class' => 'col-lg-4', 'settings' => [
                        'menu_name' => 'sonata_admin_sidebar',
                        'menu_template' => '@SonataBlock/Block/block_side_menu_template.html.twig',
                        'attr' => ['class' => 'list-group mb-3'],
                    ]],
                ],
            ],
        ]);

        // the history of posts and pages
        $container->extension('simple_things_entity_audit', [
            'audited_entities' => [Post::class, Page::class],
        ]);

        $container->extension('sonata_block', [
            'blocks' => array_fill_keys([
                'sonata.admin.block.admin_list',
                'sonata.admin.block.stats',
                'sonata.admin.block.admin_preview',
                'sonata.admin_doctrine_orm.block.audit',
                'sonata.block.service.rss',
                'sonata.block.service.text',
                'sonata.block.service.menu',
            ], ['contexts' => ['admin']]),
        ]);

        if (!$this->isOriginal()) {
            $container->extension('byteworks_sonata_theme', $this->themeOptions());
        }

        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set(LoadFixturesCommand::class);
        if ($this->isAcl()) {
            $services->set(AclUserManager::class);
        }

        // model, group, label, in the dashboard, icon of the menu item (CSS classes or HTML, as Sonata takes them)
        $admins = [
            PostAdmin::class => [Post::class, 'blog', 'Posts', true, 'fas fa-pen-to-square'],
            AuthorAdmin::class => [Author::class, 'blog', 'Authors', true, 'fas fa-user-pen'],
            CommentAdmin::class => [Comment::class, 'blog', 'Comments', false, null],
            CategoryAdmin::class => [Category::class, 'taxonomy', 'Categories', true, 'fas fa-folder-open'],
            TagAdmin::class => [Tag::class, 'taxonomy', 'Tags', true, 'fas fa-tag'],
            PageAdmin::class => [Page::class, 'content', 'Pages', true, 'fas fa-file'],
            PageLinkAdmin::class => [PageLink::class, 'content', 'Page links', false, null],
            PageSeoAdmin::class => [PageSeo::class, 'content', 'Page SEO', false, null],
            MediaAdmin::class => [Media::class, 'content', 'Media', true, '<i class="fas fa-photo-film"></i>'],
        ];
        $services->set(PageAdminController::class)->public()->tag('controller.service_arguments');
        foreach ($admins as $admin => [$model, $group, $label, $inDashboard, $icon]) {
            $definition = $services->set($admin)->tag('sonata.admin', [
                'model_class' => $model,
                'manager_type' => 'orm',
                'group' => $group,
                'label' => $label,
                'show_in_dashboard' => $inDashboard,
            ] + (null === $icon ? [] : ['item_icon' => $icon])
              + (PageAdmin::class === $admin ? ['controller' => PageAdminController::class] : []));
            if (MediaAdmin::class === $admin) {
                $definition->call('setSubClasses', [['image' => Image::class, 'video' => Video::class]]);
            }
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@SonataAdminBundle/Resources/config/routing/sonata_admin.php')->prefix('/admin');
        $routes->import('.', 'sonata_admin')->prefix('/admin');
        if (!$this->isOriginal()) {
            // the stylesheet of own colors (option "colors")
            $routes->import('@ByteworksSonataThemeBundle/config/routes.php');
        }
        // the error pages as Symfony's preview shows them (the test app replaces them with the theme's)
        $routes->import('@FrameworkBundle/Resources/config/routing/errors.php')->prefix('/_error');
        // the theme's login layout, as a project's login page uses it
        $routes->add('test_login', '/login')->controller(TemplateController::class)->defaults(['template' => 'security/login.html.twig']);
        // the home page leads to the admin (Sonata's /admin/, on to the dashboard); not permanent, so no browser keeps it
        $routes->add('test_home', '/')->controller(RedirectController::class)->defaults(['route' => 'sonata_admin_redirect', 'permanent' => false]);
    }
}
