<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security;

interface UserTokenSetterInterface
{
    public function setUser(int $userId): void;
}
