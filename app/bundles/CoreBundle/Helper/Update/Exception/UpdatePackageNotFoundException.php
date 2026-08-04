<?php

namespace Mautic\CoreBundle\Helper\Update\Exception;

final class UpdatePackageNotFoundException extends CouldNotFetchLatestVersionException
{
    protected $message = 'Update package could not be found';
}
