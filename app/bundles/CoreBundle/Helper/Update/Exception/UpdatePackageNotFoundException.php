<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\Exception;

final class UpdatePackageNotFoundException extends CouldNotFetchLatestVersionException
{
    protected $message = 'Update package could not be found';
}
