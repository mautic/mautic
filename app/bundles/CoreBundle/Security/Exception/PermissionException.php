<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Security\Exception;

class PermissionException extends \InvalidArgumentException
{
    protected $code = 403;
}
