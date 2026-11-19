<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Delivers the stylesheet of the application's own colors (option "colors"), built with the container. The layouts
 * link it with the version of the colors in the URL, so browsers may keep it for a year.
 */
final class ColorsController
{
    public function __construct(
        private readonly string $css,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ('' === $this->css) {
            return new Response('', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/css; charset=UTF-8']);
        }

        $response = new Response($this->css, Response::HTTP_OK, ['Content-Type' => 'text/css; charset=UTF-8']);
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setImmutable();
        $response->setEtag(hash('sha256', $this->css));
        $response->isNotModified($request);

        return $response;
    }
}
