<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle;

use Leezy\PheanstalkBundle\DependencyInjection\LeezyPheanstalkExtension;
use Mautic\QueueBundle\Queue\QueueProtocol;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticQueueBundle extends Bundle
{
    /**
     * @var string
     */
    private $queueProtocol;

    public function __construct(string $queueProtocol)
    {
        $this->queueProtocol = $queueProtocol;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if (!$this->queueProtocol) {
            return;
        }

        if (QueueProtocol::RABBITMQ === $this->queueProtocol) {
            $container->addCompilerPass(new RegisterPartsPass());
        }

        if (file_exists(__DIR__.'/Config/'.$this->queueProtocol.'.php')) {
            include __DIR__.'/Config/'.$this->queueProtocol.'.php';
        }
    }

    public function getContainerExtension(): ?Extension
    {
        if (null === $this->extension) {
            $this->extension = $this->createContainerExtension();
        }

        return $this->extension;
    }

    public function createContainerExtension(): ?Extension
    {
        if (QueueProtocol::RABBITMQ === $this->queueProtocol) {
            return new OldSoundRabbitMqExtension();
        }

        if (QueueProtocol::BEANSTALKD === $this->queueProtocol) {
            return new LeezyPheanstalkExtension();
        }

        return null;
    }
}
