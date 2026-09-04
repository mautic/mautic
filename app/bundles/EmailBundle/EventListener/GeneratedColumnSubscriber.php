<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $expression = DatabasePlatform::getDateOnlyExpression($platform, 'date_sent');
        $sentDate   = new GeneratedColumn('email_stats', 'generated_sent_date', 'DATE', $expression);
        $sentDate->addIndexColumn('email_id');
        $sentDate->setOriginalDateColumn('date_sent', 'd');

        $event->addGeneratedColumn($sentDate);
    }
}
