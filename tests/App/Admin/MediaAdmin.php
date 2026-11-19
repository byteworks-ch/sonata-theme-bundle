<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Image;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Media;
use Byteworks\SonataThemeBundle\Tests\App\Entity\Video;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Media with the subclasses "image" and "video" (set in the kernel): "add" first asks for the subclass.
 *
 * @extends AbstractAdmin<Media>
 */
final class MediaAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('name');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('name');
        $subject = $this->getSubject();
        if ($subject instanceof Image) {
            $form->add('width')->add('height');
        } elseif ($subject instanceof Video) {
            $form->add('duration', null, ['help' => 'In seconds.']);
        }
    }
}
