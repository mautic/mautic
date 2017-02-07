<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\DependencyInjection;

use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class MauticQueueExtension.
 *
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MauticQueueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // if ($queueProtocol = $container->getParameter('mautic.queue_protocol')) {
        //     if (file_exists(__DIR__.'/../Config/'.$queueProtocol.'.php')) {
        //         $container->registerExtension(new OldSoundRabbitMqExtension());
        //         $container->addCompilerPass(new RegisterPartsPass());
        //         include __DIR__.'/../Config/'.$queueProtocol.'.php';
        //     }
        // }
    }
}
