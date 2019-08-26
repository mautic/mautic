<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;

class ReportUtmTagSubscriber extends CommonSubscriber
{
    const UTM_TAG = 'lead.utmTag';

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    /**
     * @param FieldsBuilder     $fieldsBuilder
     * @param CompanyReportData $companyReportData
     */
    public function __construct(
        FieldsBuilder $fieldsBuilder,
        CompanyReportData $companyReportData
    ) {
        $this->fieldsBuilder     = $fieldsBuilder;
        $this->companyReportData = $companyReportData;
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
        if (!$event->checkContext([self::UTM_TAG])) {
            return;
        }

        $columns        = $this->fieldsBuilder->getLeadFieldsColumns('l.');
        $companyColumns = $this->companyReportData->getCompanyData();

        $utmTagColumns = [
            'utm.utm_campaign' => [
                'label' => 'mautic.lead.report.utm.campaign',
                'type'  => 'text',
            ],
            'utm.utm_content' => [
                'label' => 'mautic.lead.report.utm.content',
                'type'  => 'text',
            ],
            'utm.utm_medium' => [
                'label' => 'mautic.lead.report.utm.medium',
                'type'  => 'text',
            ],
            'utm.utm_source' => [
                'label' => 'mautic.lead.report.utm.source',
                'type'  => 'text',
            ],
            'utm.utm_term' => [
                'label' => 'mautic.lead.report.utm.term',
                'type'  => 'text',
            ],
        ];

        $data = [
            'display_name' => 'mautic.lead.report.utm.utm_tag',
            'columns'      => array_merge($columns, $companyColumns, $utmTagColumns),
        ];
        $event->addTable(self::UTM_TAG, $data, ReportSubscriber::GROUP_CONTACTS);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     *
     * @param ReportGeneratorEvent $event
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext([self::UTM_TAG])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.'lead_utmtags', 'utm')
            ->leftJoin('utm', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = utm.lead_id');

        if ($event->hasColumn(['u.first_name', 'u.last_name']) || $event->hasFilter(['u.first_name', 'u.last_name'])) {
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');
        }

        if ($event->hasColumn('i.ip_address') || $event->hasFilter('i.ip_address')) {
            $event->addLeadIpAddressLeftJoin($qb);
        }

        if ($this->companyReportData->eventHasCompanyColumns($event)) {
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id');
            $qb->leftJoin('companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'companies_lead.company_id = comp.id');
        }

        $event->setQueryBuilder($qb);
    }
}
