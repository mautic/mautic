<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\User;

class UserFake extends User
{
    public function __construct(?int $id = null)
    {
        $this->id = $id;
        parent::__construct();
    }
}
