<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $event->addGeneratedColumn($this->buildGeneratedColumn('hour', 'DATETIME', 'H'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('day', 'DATE', 'd', true));
        $event->addGeneratedColumn($this->buildGeneratedColumn('week', 'CHAR(7)', 'W'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('month', 'CHAR(7)', 'm'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('year', 'YEAR', 'Y'));
    }

    private function buildGeneratedColumn(string $name, string $type, string $unit, bool $filterDateColumn = false): GeneratedColumn
    {
        $columnName = 'generated_date_added_'.$name;

        // For each time unit, create an expression using only deterministic SQL functions
        // MariaDB is very restrictive about what functions can be used in generated columns
        // We need to use the most basic functions possible
        $deterministic = match ($name) {
            // For hour, use a trick: MySQL lets you store a datetime as-is, but remove the minutes and seconds
            'hour'  => 'date_added - INTERVAL MINUTE(date_added) MINUTE - INTERVAL SECOND(date_added) SECOND',
            'day'   => 'DATE(date_added)',
            'week'  => 'YEARWEEK(date_added)',
            'month' => 'EXTRACT(YEAR_MONTH FROM date_added)',
            'year'  => 'YEAR(date_added)',
            default => '',
        };

        $generatedColumn = new GeneratedColumn('campaign_leads', $columnName, $type, $deterministic);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', $unit);
        $generatedColumn->setStored(true);

        if ($filterDateColumn) {
            $generatedColumn->setFilterDateColumn($columnName);
        } else {
            $generatedColumn->addIndexColumn('date_added');
        }

        return $generatedColumn;
    }
}
