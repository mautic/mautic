<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\Role;

class RoleFake extends Role
{
    private ?int $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}