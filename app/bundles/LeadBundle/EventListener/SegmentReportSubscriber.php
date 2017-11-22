<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;

class SegmentReportSubscriber extends CommonSubscriber
{
    const SEGMENT_MEMBERSHIP = 'segment.membership';

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @param FieldsBuilder $fieldsBuilder
     */
    public function __construct(FieldsBuilder $fieldsBuilder)
    {
        $this->fieldsBuilder = $fieldsBuilder;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$event->checkContext([self::SEGMENT_MEMBERSHIP])) {
            return;
        }

        $columns = $this->fieldsBuilder->getLeadFieldsColumns('l.');

        $filters = $this->fieldsBuilder->getLeadFilter('l.', 'lll.');

        $segmentColumns = [
            'lll.manually_removed' => [
                'label' => 'mautic.lead.report.segment.manually_removed',
                'type'  => 'bool',
            ],
            'lll.manually_added' => [
                'label' => 'mautic.lead.report.segment.manually_added',
                'type'  => 'bool',
            ],
        ];

        $data = [
            'display_name' => 'mautic.lead.report.segment.membership',
            'columns'      => array_merge($columns, $segmentColumns, $event->getStandardColumns('s.', ['publish_up', 'publish_down'])),
            'filters'      => $filters,
        ];
        $event->addTable(self::SEGMENT_MEMBERSHIP, $data, ReportSubscriber::GROUP_CONTACTS);

        unset($columns, $filters, $segmentColumns, $data);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGeneratorEvent $event
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext([self::SEGMENT_MEMBERSHIP])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
            ->leftJoin('lll', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lll.lead_id')
            ->leftJoin('lll', MAUTIC_TABLE_PREFIX.'lead_lists', 's', 's.id = lll.leadlist_id')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
        $event->setQueryBuilder($qb);
    }
}
