<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Search engine data of a page (one-to-one, edited inline).
 */
#[ORM\Entity]
class PageSeo implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 200, nullable: true)]
    public ?string $metaTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $metaDescription = null;

    public function __toString(): string
    {
        return (string) $this->metaTitle;
    }
}
