<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Admin;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Comment;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Rows of the comments table in the post form.
 *
 * @extends AbstractAdmin<Comment>
 */
final class CommentAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('author', null, ['attr' => ['data-col-width' => '22%']])
            ->add('message')
            ->add('visibility', ChoiceType::class, [
                'choices' => ['Public' => 'public', 'Internal only' => 'internal'],
                'attr' => ['data-col-width' => '10%'],
            ])
            ->add('approved', null, ['required' => false])
            ->add('position');
    }
}
