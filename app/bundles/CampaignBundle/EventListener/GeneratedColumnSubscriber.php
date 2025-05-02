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
        $event->addGeneratedColumn($this->buildGeneratedColumn('hour', 'DATETIME', 'CONCAT(YEAR(date_added), "-", LPAD(MONTH(date_added), 2, "0"), "-", LPAD(DAY(date_added), 2, "0"), " ", LPAD(HOUR(date_added), 2, "0"), ":00:00")', 'H'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('day', 'DATE', 'CONCAT(YEAR(date_added), "-", LPAD(MONTH(date_added), 2, "0"), "-", LPAD(DAY(date_added), 2, "0"))', 'd', true));
        $event->addGeneratedColumn($this->buildGeneratedColumn('week', 'CHAR(7)', 'CONCAT(YEAR(date_added), " ", LPAD(FLOOR((DAYOFYEAR(date_added) - 1) / 7) + 1, 2, "0"))', 'W'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('month', 'CHAR(7)', 'CONCAT(YEAR(date_added), "-", LPAD(MONTH(date_added), 2, "0"))', 'm'));
        $event->addGeneratedColumn($this->buildGeneratedColumn('year', 'YEAR', 'YEAR(date_added)', 'Y'));
    }

    private function buildGeneratedColumn(string $name, string $type, string $expression, string $unit, bool $filterDateColumn = false): GeneratedColumn
    {
        $columnName      = 'generated_date_added_'.$name;
        $generatedColumn = new GeneratedColumn('campaign_leads', $columnName, $type, $expression);
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
