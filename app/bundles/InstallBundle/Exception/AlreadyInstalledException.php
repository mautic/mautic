<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Exception;

final class AlreadyInstalledException extends \Exception
{
    protected $message = 'Mautic is already installed.';
}
