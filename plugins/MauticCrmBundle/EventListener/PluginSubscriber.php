<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private PluginDatabase $pluginDatabase)
    {
    }

    public function onInstall(PluginInstallEvent $event): void
    {
        $eventMetadata = $event->getMetadata();

        if (null === $eventMetadata) {
            $metadata = self::getMetadata($this->entityManager);
        } else {
            $metadata = [];
            foreach ($eventMetadata as $class => $classMetadata) {
                if (!str_contains($classMetadata->namespace, 'MauticPlugin\\MauticCrmBundle')) {
                    continue;
                }

                $metadata[$class] = $classMetadata;
            }
        }

        if (count($metadata) > 0) {
            $this->pluginDatabase->installPluginSchema(
                $metadata,
                $event->getInstalledSchema()
            );
        }
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::ON_PLUGIN_INSTALL => ['onInstall', 100],
        ];
    }

    /**
     * Fix: plugin installer doesn't find metadata entities for the plugin
     * PluginBundle/Controller/PluginController:410.
     *
     * @return array<class-string, ClassMetadata>
     */
    private static function getMetadata(EntityManagerInterface $em): array
    {
        $allMetadata   = $em->getMetadataFactory()->getAllMetadata();
        $currentSchema = $em->getConnection()->createSchemaManager()->introspectSchema();

        $classes = [];

        /** @var ClassMetadata $meta */
        foreach ($allMetadata as $meta) {
            if (!str_contains($meta->namespace, 'MauticPlugin\\MauticCrmBundle')) {
                continue;
            }

            $table = $meta->getTableName();

            if ($currentSchema->hasTable($table)) {
                continue;
            }

            $classes[$meta->namespace] = $meta;
        }

        return $classes;
    }
}
