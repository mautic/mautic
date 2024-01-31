<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WeakPasswordException extends AuthenticationException
{
}
