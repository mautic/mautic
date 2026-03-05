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
    private const TABLE_NAME    = 'campaign_leads';
    private const DATE_COLUMN   = 'date_added';
    private const INDEX_COLUMN  = 'campaign_id';

    private const DEFINITIONS = [
        'hour' => [
            'column'      => 'generated_date_added_hour',
            'type'        => 'varchar(16)',
            'mysql'       => 'DATE_FORMAT('.self::DATE_COLUMN.', "%Y-%m-%d %H:00")',
            'postgres'    => 'to_char('.self::DATE_COLUMN.', \'YYYY-MM-DD HH24:00\')',
            'granularity' => 'H',
            'filter'      => false,
        ],
        'day' => [
            'column'      => 'generated_date_added_day',
            'type'        => 'date',
            'mysql'       => 'DATE_FORMAT('.self::DATE_COLUMN.', "%Y-%m-%d")',
            'postgres'    => self::DATE_COLUMN.'::date',
            'granularity' => 'd',
            'filter'      => true,
        ],
        'week' => [
            'column'      => 'generated_date_added_week',
            'type'        => 'char(8)',
            'mysql'       => 'DATE_FORMAT('.self::DATE_COLUMN.', "%Y %U")',
            'postgres'    => 'to_char('.self::DATE_COLUMN.', \'IYYY IW\')',
            'granularity' => 'W',
            'filter'      => false,
        ],
        'month' => [
            'column'      => 'generated_date_added_month',
            'type'        => 'char(7)',
            'mysql'       => 'DATE_FORMAT('.self::DATE_COLUMN.', "%Y-%m")',
            'postgres'    => 'to_char('.self::DATE_COLUMN.', \'YYYY-MM\')',
            'granularity' => 'm',
            'filter'      => false,
        ],
        'year' => [
            'column'      => 'generated_date_added_year',
            'type'        => 'smallint',
            'mysql'       => 'DATE_FORMAT('.self::DATE_COLUMN.', "%Y")',
            'postgres'    => 'EXTRACT(YEAR FROM '.self::DATE_COLUMN.')::smallint',
            'granularity' => 'Y',
            'filter'      => false,
        ],
    ];

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
        $isMySQL = $this->versionProvider->isMySql();

        if (!$isMySQL && !$this->versionProvider->isPostgreSql()) {
            return;
        }

        foreach (self::DEFINITIONS as $def) {
            $expr = $isMySQL ? $def['mysql'] : $def['postgres'];

            $generatedColumn = new GeneratedColumn(
                self::TABLE_NAME,
                $def['column'],
                $def['type'],
                $expr
            );

            $generatedColumn->prependIndexColumn(self::INDEX_COLUMN);
            $generatedColumn->setOriginalDateColumn(self::DATE_COLUMN, $def['granularity']);
            $generatedColumn->setStored(true);

            if ($def['filter']) {
                $generatedColumn->setFilterDateColumn($def['column']);
            }

            $event->addGeneratedColumn($generatedColumn);
        }
    }
}
