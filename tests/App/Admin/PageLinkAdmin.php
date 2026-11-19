<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\PageLink;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Links of a page, edited inline as tabs (one tab per form group).
 *
 * @extends AbstractAdmin<PageLink>
 */
final class PageLinkAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('link')
                ->add('label')
                ->add('url')
                ->add('position', HiddenType::class)
            ->end()
            ->with('details')
                ->add('note', null, ['required' => false])
            ->end();
    }
}
