<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\EventListener;

use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly PluginDatabase $pluginDatabase)
    {
    }

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

    public function onUpdate(PluginUpdateEvent $event): void
    {
        $this->pluginDatabase->onPluginUpdate($event->getPlugin());
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::ON_PLUGIN_INSTALL => ['onInstall', 0],
            PluginEvents::ON_PLUGIN_UPDATE  => ['onUpdate', 0],
        ];
    }
}
