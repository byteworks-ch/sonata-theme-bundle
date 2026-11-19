<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Link of a page (one-to-many, edited inline as tabs, sortable).
 */
#[ORM\Entity]
class PageLink implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Page $page = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    public ?string $label = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $note = null;

    #[ORM\Column]
    public int $position = 0;

    public function __toString(): string
    {
        return (string) $this->label;
    }
}
