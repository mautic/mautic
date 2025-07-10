<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportDevicesSubscriber implements EventSubscriberInterface
{
    public const DEVICES = 'contact.devices';

    public function __construct(
        private FieldsBuilder $fieldsBuilder,
        private CompanyReportData $companyReportData,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY  => ['onReportDisplay', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::DEVICES])) {
            return;
        }

        $columns        = $this->fieldsBuilder->getLeadFieldsColumns('l.');
        $companyColumns = $this->companyReportData->getCompanyData();
        $filters        = $this->fieldsBuilder->getLeadFilter('l.', 's.');

        $devicesColumns = [
            'dev.date_added' => [
                'label' => 'mautic.lead.report.dev_date_added',
                'type'  => 'datetime',
            ],
            'dev.client_info' => [
                'label' => 'mautic.lead.report.dev_client_info',
                'type'  => 'html',
            ],
            'dev.device' => [
                'label' => 'mautic.lead.report.dev_device',
                'type'  => 'text',
            ],
            'dev.device_os_name' => [
                'label' => 'mautic.lead.report.dev_device_os_name',
                'type'  => 'text',
            ],
            'dev.device_os_version' => [
                'label' => 'mautic.lead.report.dev_device_os_version',
                'type'  => 'text',
            ],
            'dev.device_os_platform' => [
                'label' => 'mautic.lead.report.dev_device_os_platform',
                'type'  => 'text',
            ],
            'dev.device_brand' => [
                'label' => 'mautic.lead.report.dev_device_brand',
                'type'  => 'text',
            ],
            'dev.device_model' => [
                'label' => 'mautic.lead.report.dev_device_model',
                'type'  => 'text',
            ],
        ];

        $devicesFilters = $devicesColumns;
        unset($devicesFilters['dev.client_info']);

        $data = [
            'display_name' => 'mautic.lead.report.devices',
            'columns'      => array_merge($columns, $companyColumns, $devicesColumns),
            'filters'      => array_merge($filters, $devicesFilters),
        ];
        $event->addTable(self::DEVICES, $data, ReportSubscriber::GROUP_CONTACTS);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::DEVICES])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'dev');
        $qb->leftJoin('dev', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dev.lead_id');

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

        if ($event->hasFilter('s.leadlist_id')) {
            $qb->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 's', 's.lead_id = l.id AND s.manually_removed = 0');
        }

        $event->setQueryBuilder($qb);
    }

    public function onReportDisplay(ReportDataEvent $event): void
    {
        if (!$event->checkContext([self::DEVICES])) {
            return;
        }

        $data = $event->getData();
        if (isset($data[0]['client_info'])) {
            foreach ($data as &$row) {
                $clientInfo         = unserialize($row['client_info']);
                $row['client_info'] = (is_array($clientInfo) && isset($clientInfo['name'])) ? $clientInfo['name'] : '';
            }
            $event->setData($data);
        }
    }
}
