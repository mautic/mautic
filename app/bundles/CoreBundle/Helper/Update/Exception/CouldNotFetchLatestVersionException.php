<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\Exception;

class CouldNotFetchLatestVersionException extends \Exception
{
    protected $message = 'Could not determine a compatible release to upgrade to';
}
