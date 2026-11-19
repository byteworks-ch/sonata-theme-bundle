<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Post implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    public ?string $title = null;

    #[ORM\Column(type: 'text')]
    public ?string $body = null;

    #[ORM\Column]
    public bool $published = false;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne]
    public ?Category $category = null;

    #[ORM\ManyToOne]
    public ?Author $author = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    public Collection $tags;

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private Collection $comments;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /** needed by access control lists (environment "acl") */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $comment->post = $this;
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
