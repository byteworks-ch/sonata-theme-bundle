<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Page;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ChoiceFieldMaskType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @extends AbstractAdmin<Page>
 */
final class PageAdmin extends AbstractAdmin
{
    public const SECTIONS = ['Header' => 'header', 'Introduction' => 'intro', 'Gallery' => 'gallery', 'Comments' => 'comments', 'Footer' => 'footer'];

    /** the form has a "preview" button */
    protected $supportsPreviewMode = true;

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('summary', $this->getRouterIdParameter().'/summary');
        $collection->add('popup', $this->getRouterIdParameter().'/popup');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title')
            ->add('format')
            ->add('reviewer')
            ->add(ListMapper::NAME_ACTIONS, null, ['actions' => ['edit' => [], 'history' => [], 'delete' => []]]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('title')
            ->add('reviewer', ModelFilter::class, [
                'field_type' => ModelAutocompleteType::class,
                'field_options' => ['property' => 'name'],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('content')
                ->with('page', ['class' => 'col-md-8'])
                    ->add('title')
                    ->add('format', ChoiceFieldMaskType::class, [
                        'choices' => ['Text' => 'text', 'Link' => 'link'],
                        'map' => ['text' => ['body'], 'link' => ['url']],
                    ])
                    ->add('body', TextareaType::class, ['required' => false, 'attr' => ['rows' => 4]])
                    ->add('url', null, ['required' => true])
                    ->add('sections', ChoiceType::class, [
                        'choices' => self::SECTIONS,
                        'multiple' => true,
                        'sortable' => true,
                        // otherwise Symfony merges the submitted values into the old ones and keeps their order
                        'by_reference' => false,
                        'required' => false,
                        'help' => 'Drag the sections into the order of the page.',
                    ])
                ->end()
                ->with('relations', ['class' => 'col-md-4', 'box_class' => 'box box-success'])
                    ->add('reviewer', ModelAutocompleteType::class, [
                        'property' => 'name',
                        'required' => false,
                        'btn_add' => 'Add author',
                        'minimum_input_length' => 1,
                    ])
                    ->add('related', ModelAutocompleteType::class, [
                        'property' => 'title',
                        'multiple' => true,
                        'required' => false,
                        'items_per_page' => 5,
                    ])
                    ->add('featuredTag', ModelListType::class, ['required' => false])
                ->end()
            ->end()
            ->tab('links')
                ->with('links')
                    ->add('links', CollectionType::class, ['by_reference' => false], [
                        'edit' => 'inline',
                        'inline' => 'tabs',
                        'sortable' => 'position',
                    ])
                ->end()
            ->end()
            ->tab('more')
                ->with('seo', ['class' => 'col-md-6'])
                    ->add('seo', AdminType::class, ['required' => false, 'delete' => false], ['edit' => 'inline'])
                ->end()
                ->with('labels', ['class' => 'col-md-6'])
                    ->add('labels', CollectionType::class, ['by_reference' => false, 'required' => false], [
                        'edit' => 'inline',
                        'inline' => 'standard',
                    ])
                ->end()
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('title')
            ->add('format')
            ->add('body')
            ->add('url')
            ->add('reviewer')
            ->add('related')
            ->add('featuredTag')
            ->add('labels');
    }
}
