<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Image extends Media
{
    #[ORM\Column(nullable: true)]
    public ?int $width = null;

    #[ORM\Column(nullable: true)]
    public ?int $height = null;
}
