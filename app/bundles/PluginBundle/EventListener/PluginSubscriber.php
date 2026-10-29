<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;

final readonly class PluginSubscriber
{
    public function __construct(
        private PluginDatabase $pluginDatabase,
    ) {
    }

    #[AsEventListener(event: PluginInstallEvent::class)]
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

    #[AsEventListener(event: PluginUpdateEvent::class)]
    public function onUpdate(PluginUpdateEvent $event): void
    {
        $this->pluginDatabase->onPluginUpdate($event->getPlugin());
    }
}
