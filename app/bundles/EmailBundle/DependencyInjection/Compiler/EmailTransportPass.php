<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Mautic\EmailBundle\Model\TransportType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class EmailTransportPass.
 */
class EmailTransportPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.email.transport_type')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.email.transport_type');
        $taggedServices = $container->findTaggedServiceIds('mautic.email_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                !empty($tags[0][TransportType::TRANSPORT_ALIAS]) ? $tags[0][TransportType::TRANSPORT_ALIAS] : $id,
                !empty($tags[0][TransportType::FIELD_HOST]),
                !empty($tags[0][TransportType::FIELD_PORT]),
                !empty($tags[0][TransportType::FIELD_USER]),
                !empty($tags[0][TransportType::FIELD_PASSWORD]),
                !empty($tags[0][TransportType::FIELD_API_KEY]),
            ]);
        }
    }
}
