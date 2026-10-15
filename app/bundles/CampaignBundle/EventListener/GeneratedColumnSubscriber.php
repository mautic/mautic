<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VersionProviderInterface $versionProvider,
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
        if ($this->versionProvider->isPostgreSql()) {
            $event->addGeneratedColumn($this->buildGeneratedColumn('hour', 'TIMESTAMP(0) WITHOUT TIME ZONE', "date_trunc('hour', date_added)", 'H'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('day', 'DATE', 'date_added::date', 'd', true));
            // Match MySQL %Y %U: calendar year, Sunday-based weeks, and week 00 before the first Sunday.
            $event->addGeneratedColumn($this->buildGeneratedColumn('week', 'CHAR(7)', "EXTRACT(YEAR FROM date_added)::int::text || ' ' || LPAD(((EXTRACT(DOY FROM date_added)::int + 6 - EXTRACT(DOW FROM date_added)::int) / 7)::text, 2, '0')", 'W'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('month', 'CHAR(7)', "EXTRACT(YEAR FROM date_added)::int::text || '-' || LPAD(EXTRACT(MONTH FROM date_added)::int::text, 2, '0')", 'm'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('year', 'INTEGER', 'EXTRACT(YEAR FROM date_added)::int', 'Y'));
        } elseif ($this->versionProvider->isMySql()) {
            $event->addGeneratedColumn($this->buildGeneratedColumn('hour', 'DATETIME', 'DATE_FORMAT(date_added, "%Y-%m-%d %H:00")', 'H'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('day', 'DATE', 'DATE_FORMAT(date_added, "%Y-%m-%d")', 'd', true));
            $event->addGeneratedColumn($this->buildGeneratedColumn('week', 'CHAR(7)', 'DATE_FORMAT(date_added, "%Y %U")', 'W'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('month', 'CHAR(7)', 'DATE_FORMAT(date_added, "%Y-%m")', 'm'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('year', 'YEAR', 'DATE_FORMAT(date_added, "%Y")', 'Y'));
        }
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
