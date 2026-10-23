<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber that registers generated columns for the dynamic content stats table.
 * The virtual column and its index are automatically created by MigrationCommandSubscriber
 * after running doctrine:migrations:migrate.
 */
final class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $sourceDataUri = new GeneratedColumn(
            'dynamic_content_stats',
            'source_data_uri',
            'VARCHAR(4096)',
            'JSON_UNQUOTE(JSON_EXTRACT(source_data, "$.uri"))'
        );

        $sourceDataUri->setIndexColumns(['source_data_uri(100)']);

        $event->addGeneratedColumn($sourceDataUri);
    }
}
