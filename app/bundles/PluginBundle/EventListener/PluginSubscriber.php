<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\EventListener;

use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PluginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginDatabase $pluginDatabase,
    ) {
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
            PluginInstallEvent::class => ['onInstall', 0],
            PluginUpdateEvent::class  => ['onUpdate', 0],
        ];
    }
}
