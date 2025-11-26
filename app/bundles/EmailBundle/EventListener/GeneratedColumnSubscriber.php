<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
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
        if ('pdo_pgsql' == $this->coreParametersHelper->get('db_driver')) {
            // PostgreSQL syntax – fully supported since PG 12
            $expression = 'make_date(EXTRACT(YEAR FROM date_sent)::int, EXTRACT(MONTH FROM date_sent)::int, EXTRACT(DAY FROM date_sent)::int)';
        } else {
            // MySQL / MariaDB syntax (kept original qiery)
            $expression = "CONCAT(YEAR(date_sent), '-', LPAD(MONTH(date_sent), 2, '0'), '-', LPAD(DAY(date_sent), 2, '0'))";
        }

        $sentDate = new GeneratedColumn('email_stats', 'generated_sent_date', 'DATE', $expression);
        $sentDate->addIndexColumn('email_id');
        $sentDate->setOriginalDateColumn('date_sent', 'd');

        $event->addGeneratedColumn($sentDate);
    }
}
