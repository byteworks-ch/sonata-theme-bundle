<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Video extends Media
{
    /** in seconds */
    #[ORM\Column(nullable: true)]
    public ?int $duration = null;
}
