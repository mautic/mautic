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
        /*
         * Currently only MySQL support generated columns.
         *
         * We disable it on postgresql by default as the expressions we try to use are not immutable
         * ERROR:  generation expression is not immutable
         *
         * As workaround for postgresql in future we should probably use
         * standard column + trigger on update/edit which will fill the data.
         */
        if ($this->versionProvider->isMySql()) {
            $event->addGeneratedColumn($this->buildGeneratedColumn('hour', 'DATETIME', '%Y-%m-%d %H:00', 'H'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('day', 'DATE', '%Y-%m-%d', 'd', true));
            $event->addGeneratedColumn($this->buildGeneratedColumn('week', 'CHAR(7)', '%Y %U', 'W'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('month', 'CHAR(7)', '%Y-%m', 'm'));
            $event->addGeneratedColumn($this->buildGeneratedColumn('year', 'YEAR', '%Y', 'Y'));
        }
    }

    private function buildGeneratedColumn(string $name, string $type, string $format, string $unit, bool $filterDateColumn = false): GeneratedColumn
    {
        $columnName      = 'generated_date_added_'.$name;
        $generatedColumn = new GeneratedColumn('campaign_leads', $columnName, $type, 'DATE_FORMAT(date_added, "'.$format.'")');
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
