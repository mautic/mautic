<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Exception\MessageOnlyErrorHandlerException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequirementsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (defined('MAUTIC_INSTALLER')) {
            // The installer itself does the PDO check, so no need to validate here during installation.
            return;
        }

        if (!class_exists('PDO') || !in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            // We need to check this on boot, as later in the process is too late to show a message that makes the issue clear.
            throw new MessageOnlyErrorHandlerException('Mautic requires the PHP pdo_mysql extension to work. Please ensure this extension is installed and enabled');
        }
    }
}
