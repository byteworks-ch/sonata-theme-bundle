<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Author;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * @extends AbstractAdmin<Author>
 */
final class AuthorAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('name')->add('email')->add('category')
            // a long cell: one line, the full content on hover (class adminlte4-clamp of the theme), in a column with a
            // width of its own
            ->add('topics', FieldDescriptionInterface::TYPE_STRING, ['virtual_field' => true, 'label' => 'Topics', 'template' => 'admin/author_topics.html.twig', 'header_style' => 'width: 50%']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('email');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name')
            ->add('email')
            ->add('category', ModelListType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('name')->add('email')->add('category');
    }
}
