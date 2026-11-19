<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Comment implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Post $post = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    public ?string $author = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public ?string $message = null;

    #[ORM\Column]
    public bool $approved = false;

    /** "public" or "internal": a select in a narrow column of the inline table */
    #[ORM\Column(length: 10)]
    public string $visibility = 'public';

    #[ORM\Column]
    public int $position = 0;

    public function __toString(): string
    {
        return (string) $this->author;
    }
}
