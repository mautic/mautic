<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AttributeMappedEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    public function loadMetadata(): void
    {
    }
}
