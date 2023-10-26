<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Exception;

use Exception;

class PluginNotConfiguredException extends Exception
{
    protected $message = 'mautic.integration.not_configured';
}
