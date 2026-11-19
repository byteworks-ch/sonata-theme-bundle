<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Author implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    public ?string $name = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email]
    public ?string $email = null;

    /** Category the author mostly writes in: a list modal inside the "add author" modal (stacked modals). */
    #[ORM\ManyToOne]
    public ?Category $category = null;

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
