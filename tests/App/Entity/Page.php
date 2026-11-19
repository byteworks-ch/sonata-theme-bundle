<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A page with the form widgets that need more than the post: autocomplete, choice field mask, sortable choices,
 * one-to-one associations, inline tabs and inline many-to-many.
 */
#[ORM\Entity]
class Page implements \Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    public ?string $title = null;

    /** "text" shows the body, "link" the URL */
    #[ORM\Column(length: 10)]
    public string $format = 'text';

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $body = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: false)]
    public ?string $url = null;

    #[ORM\ManyToOne]
    public ?Author $reviewer = null;

    /** @var Collection<int, Post> */
    #[ORM\ManyToMany(targetEntity: Post::class)]
    #[ORM\JoinTable(name: 'page_related_post')]
    public Collection $related;

    /** @var list<string> */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    public ?array $sections = [];

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    public ?PageSeo $seo = null;

    #[ORM\OneToOne]
    public ?Tag $featuredTag = null;

    /** @var Collection<int, PageLink> */
    #[ORM\OneToMany(targetEntity: PageLink::class, mappedBy: 'page', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private Collection $links;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'page_label')]
    #[Assert\Valid]
    public Collection $labels;

    public function __construct()
    {
        $this->related = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    /** @return Collection<int, PageLink> */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(PageLink $link): void
    {
        $link->page = $this;
        $this->links->add($link);
    }

    public function removeLink(PageLink $link): void
    {
        $this->links->removeElement($link);
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
