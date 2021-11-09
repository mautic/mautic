<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SpoolTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Override Swiftmailer's \Swift_TransportSpool with our own that delegates writing to the filesystem or sending immediately
        // based on the configuration
        $container->setAlias('swiftmailer.mailer.default.transport', 'mautic.transport.spool');
    }
}
