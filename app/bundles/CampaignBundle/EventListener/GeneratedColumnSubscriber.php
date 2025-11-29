<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(private VersionProviderInterface $versionProvider)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $isPostgreSQL = str_contains($this->versionProvider->getVersion(), 'PostgreSQL');
        $isMySQL      = $this->versionProvider->isMySql();

        if (!$isMySQL && !$isPostgreSQL) {
            return;
        }

        $event->addGeneratedColumn($this->buildHourColumn($isMySQL));
        $event->addGeneratedColumn($this->buildDayColumn($isMySQL));
        $event->addGeneratedColumn($this->buildWeekColumn($isMySQL));
        $event->addGeneratedColumn($this->buildMonthColumn($isMySQL));
        $event->addGeneratedColumn($this->buildYearColumn($isMySQL));
    }

    private function buildHourColumn(bool $my): GeneratedColumn
    {
        $expr = $my
            ? 'DATE_FORMAT(date_added, "%Y-%m-%d %H:00")'
            : "to_char(date_added, 'YYYY-MM-DD HH24:00')";

        $generatedColumn = new GeneratedColumn('campaign_leads', 'generated_date_added_hour', 'varchar(16)', $expr);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', 'H');
        $generatedColumn->setStored(true);

        return $generatedColumn;
    }

    private function buildDayColumn(bool $my): GeneratedColumn
    {
        $expr = $my
            ? 'DATE_FORMAT(date_added, "%Y-%m-%d")'
            : 'date_added::date';

        $generatedColumn = new GeneratedColumn('campaign_leads', 'generated_date_added_day', 'date', $expr);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', 'd');
        $generatedColumn->setStored(true);
        $generatedColumn->setFilterDateColumn('generated_date_added_day');
        return $generatedColumn;
    }

    private function buildWeekColumn(bool $my): GeneratedColumn
    {
        $expr = $my
            ? 'DATE_FORMAT(date_added, "%Y %U")'
            : "to_char(date_added, 'IYYY IW')";               // ISO year + week

        $generatedColumn = new GeneratedColumn('campaign_leads', 'generated_date_added_week', 'char(8)', $expr);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', 'W');
        $generatedColumn->setStored(true);
        return $generatedColumn;
    }

    private function buildMonthColumn(bool $my): GeneratedColumn
    {
        $expr = $my
            ? 'DATE_FORMAT(date_added, "%Y-%m")'
            : "to_char(date_added, 'YYYY-MM')";

        $generatedColumn = new GeneratedColumn('campaign_leads', 'generated_date_added_month', 'char(7)', $expr);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', 'm');
        $generatedColumn->setStored(true);
        return $generatedColumn;
    }

    private function buildYearColumn(bool $my): GeneratedColumn
    {
        $expr = $my
            ? 'DATE_FORMAT(date_added, "%Y")'
            : 'EXTRACT(YEAR FROM date_added)::smallint';

        $generatedColumn = new GeneratedColumn('campaign_leads', 'generated_date_added_year', 'smallint', $expr);
        $generatedColumn->prependIndexColumn('campaign_id');
        $generatedColumn->setOriginalDateColumn('date_added', 'Y');
        $generatedColumn->setStored(true);
        return $generatedColumn;
    }
}
