<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\RepeatablePassInterface;
use Symfony\Component\DependencyInjection\Compiler\RepeatedPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SmsTransportPass implements CompilerPassInterface, RepeatablePassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.sms.transport_chain')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.sms.transport_chain');
        $taggedServices = $container->findTaggedServiceIds('mautic.sms_transport');

        foreach ($taggedServices as $id => $tags) {
            $serviceTags   = $container->findDefinition($id)->getTags();
            $serviceTags   = array_keys($serviceTags);
            $integrationId = array_pop($serviceTags);

            $definition->addMethodCall('addTransport', [
                    $id,
                    new Reference($id),
                    $tags[0]['alias'],
                    $integrationId,
                ]);
        }
    }

    public function setRepeatedPass(RepeatedPass $repeatedPass)
    {
        $this->repeatedPass = $repeatedPass;
    }
}
