<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Category;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * @extends AbstractAdmin<Category>
 */
final class CategoryAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('name');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('name');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('name');
    }
}
