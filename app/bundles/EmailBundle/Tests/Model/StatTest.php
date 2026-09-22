<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\EmailBundle\Entity\Stat;

final class StatTest extends Stat
{
    private ?string $id = null;

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
