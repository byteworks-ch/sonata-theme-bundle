<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\PageSeo;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Search engine data of a page, edited inline (one-to-one).
 *
 * @extends AbstractAdmin<PageSeo>
 */
final class PageSeoAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('metaTitle', null, ['required' => false])
            ->add('metaDescription', null, ['required' => false]);
    }
}
