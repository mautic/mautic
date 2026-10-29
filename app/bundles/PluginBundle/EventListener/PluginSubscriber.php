<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\EventListener;

use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PluginSubscriber
{
    public function __construct(
        private PluginDatabase $pluginDatabase,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: PluginInstallEvent::class)]
    public function onInstall(PluginInstallEvent $event): void
    {
        $metadata = $event->getMetadata();

        if (null === $metadata) {
            return;
        }

        $this->pluginDatabase->installPluginSchema(
            $metadata,
            $event->getInstalledSchema()
        );
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: PluginUpdateEvent::class)]
    public function onUpdate(PluginUpdateEvent $event): void
    {
        $this->pluginDatabase->onPluginUpdate($event->getPlugin());
    }
}
