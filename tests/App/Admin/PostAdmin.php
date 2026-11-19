<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Post;
use Knp\Menu\ItemInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\DateTimePickerType;
use Sonata\Form\Type\DateTimeRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @extends AbstractAdmin<Post>
 */
final class PostAdmin extends AbstractAdmin
{
    /**
     * Tab menu of the show and edit pages: links to both pages and a dropdown (as applications build them).
     */
    protected function configureTabMenu(ItemInterface $menu, string $action, ?AdminInterface $childAdmin = null): void
    {
        if (!\in_array($action, ['show', 'edit'], true) || !$this->hasSubject()) {
            return;
        }

        $id = $this->getUrlSafeIdentifier($this->getSubject());
        $menu->addChild('Show', $this->generateMenuUrl('show', ['id' => $id]))->setAttribute('icon', 'fas fa-eye');
        $menu->addChild('Edit', $this->generateMenuUrl('edit', ['id' => $id]))->setAttribute('icon', 'fas fa-pencil-alt');
        $more = $menu->addChild('More', ['attributes' => ['dropdown' => true]]);
        $more->addChild('All posts', $this->generateMenuUrl('list'));
        $more->addChild('New post', $this->generateMenuUrl('create'))->setAttribute('divider_prepend', true);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title')
            ->add('category')
            ->add('author')
            ->add('published', null, ['editable' => true])
            ->add('publishedAt')
            ->add(ListMapper::NAME_ACTIONS, null, ['actions' => ['show' => [], 'edit' => [], 'delete' => []]]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('title')
            ->add('published')
            ->add('category')
            ->add('publishedAt', DateTimeRangeFilter::class, ['field_type' => DateTimeRangePickerType::class]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('content')
                ->with('post', ['class' => 'col-md-8'])
                    ->add('title')
                    ->add('body', TextareaType::class, ['attr' => ['rows' => 6], 'help' => 'Plain text.'])
                ->end()
                ->with('relations', ['class' => 'col-md-4', 'box_class' => 'box box-info'])
                    ->add('category', ModelListType::class, ['required' => false])
                    // search in the dropdown from three options on (Sonata's attribute, default 10)
                    ->add('author', ModelType::class, ['required' => false, 'btn_add' => 'Add author', 'attr' => ['data-sonata-select2-minimum-results-for-search' => 3]])
                    ->add('tags', ModelType::class, ['multiple' => true, 'required' => false])
                ->end()
            ->end()
            ->tab('comments')
                ->with('comments')
                    ->add('comments', CollectionType::class, ['by_reference' => false], [
                        'edit' => 'inline',
                        'inline' => 'table',
                        'sortable' => 'position',
                    ])
                ->end()
            ->end()
            ->tab('settings')
                ->with('publication')
                    ->add('published')
                    ->add('publishedAt', DateTimePickerType::class, ['required' => false])
                ->end()
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->tab('content')
                ->with('post', ['class' => 'col-md-8'])
                    ->add('title')
                    ->add('body')
                ->end()
                ->with('relations', ['class' => 'col-md-4', 'box_class' => 'box box-info'])
                    ->add('category')
                    ->add('author')
                    ->add('tags')
                ->end()
            ->end()
            ->tab('settings')
                ->with('publication')
                    ->add('published')
                    ->add('publishedAt')
                ->end()
            ->end();
    }
}
