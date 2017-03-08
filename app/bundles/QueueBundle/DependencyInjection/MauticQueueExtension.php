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

use Leezy\PheanstalkBundle\DependencyInjection\LeezyPheanstalkExtension;
use Mautic\QueueBundle\Queue\QueueProtocol;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class MauticQueueExtension.
 *
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MauticQueueExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$queueProtocol = $container->getParameter('mautic.queue_protocol')) {
            return;
        }

        if ($queueProtocol == QueueProtocol::RABBITMQ) {
            $container->registerExtension(new OldSoundRabbitMqExtension());
            $container->addCompilerPass(new RegisterPartsPass());
        }

        if ($queueProtocol == QueueProtocol::BEANSTALKD) {
            $container->registerExtension(new LeezyPheanstalkExtension());
        }

        if (file_exists(__DIR__.'/../Config/'.$queueProtocol.'.php')) {
            include __DIR__.'/../Config/'.$queueProtocol.'.php';
        }
    }
}
