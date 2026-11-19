<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Controller;

use Byteworks\SonataThemeBundle\Tests\App\Entity\Page;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom actions of the page admin: one with Sonata's base template for actions, one in the layout without header
 * and sidebar (e.g. for a popup window).
 *
 * @extends CRUDController<Page>
 */
final class PageAdminController extends CRUDController
{
    public function summaryAction(Request $request): Response
    {
        $object = $this->assertObjectExists($request, true);
        \assert($object instanceof Page);
        $this->admin->checkAccess('show', $object);
        $this->admin->setSubject($object);

        return $this->renderWithExtraParams('page/summary.html.twig', ['object' => $object, 'action' => 'summary']);
    }

    public function popupAction(Request $request): Response
    {
        $object = $this->assertObjectExists($request, true);
        \assert($object instanceof Page);
        $this->admin->checkAccess('show', $object);

        return $this->renderWithExtraParams('page/popup.html.twig', ['object' => $object, 'action' => 'popup']);
    }
}
