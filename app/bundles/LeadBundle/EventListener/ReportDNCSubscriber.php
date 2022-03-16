<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ReportDNCSubscriber implements EventSubscriberInterface
{
    const DNC = 'contact.dnc';

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    public function __construct(
        FieldsBuilder $fieldsBuilder,
        CompanyReportData $companyReportData,
        TranslatorInterface $translator,
        RouterInterface $router,
        ChannelListHelper $channelListHelper
    ) {
        $this->fieldsBuilder     = $fieldsBuilder;
        $this->companyReportData = $companyReportData;
        $this->translator        = $translator;
        $this->router            = $router;
        $this->channelListHelper = $channelListHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$event->checkContext([self::DNC])) {
            return;
        }

        $columns        = $this->fieldsBuilder->getLeadFieldsColumns('l.');
        $companyColumns = $this->companyReportData->getCompanyData();

        $dncColumns = [
            'dnc.reason' => [
                'label' => 'mautic.lead.report.dnc_reason',
                'type'  => 'select',
                'list'  => $this->getDncReasons(),
            ],
            'dnc.comments' => [
                'label' => 'mautic.lead.report.dnc_comment',
                'type'  => 'text',
            ],
            'dnc.date_added' => [
                'label' => 'mautic.lead.report.dnc_date_added',
                'type'  => 'datetime',
            ],
            'dnc.channel' => [
                'label' => 'mautic.lead.report.dnc_channel',
                'type'  => 'html',
            ],
            'dnc.channel_id' => [
                'label' => 'mautic.lead.report.dnc_channel_id',
                'type'  => 'html',
            ],
        ];

        $data = [
            'display_name' => 'mautic.lead.report.dnc',
            'columns'      => array_merge($columns, $companyColumns, $dncColumns),
        ];
        $event->addTable(self::DNC, $data, ReportSubscriber::GROUP_CONTACTS);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext([self::DNC])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc');
        $qb->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id');

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

    public function onReportDisplay(ReportDataEvent $event)
    {
        if (!$event->checkContext([self::DNC])) {
            return;
        }

        $data = $event->getData();

        if (isset($data[0]['reason']) || isset($data[0]['channel']) || isset($data[0]['channel_id'])) {
            foreach ($data as &$row) {
                if (isset($row['reason'])) {
                    $row['reason'] = $this->getDncReasonLabel($row['reason']);
                }

                if (isset($row['channel']) && isset($row['channel_id'])) {
                    $href              = $this->router->generate('mautic_'.$row['channel'].'_action', ['objectAction' => 'view', 'objectId' => $row['channel_id']]);
                    $row['channel']    = '<a href="'.$href.'" data-toggle="ajax">'.$this->channelListHelper->getChannelLabel($row['channel']).'</a>';
                    $row['channel_id'] = '<a href="'.$href.'" data-toggle="ajax">'.$row['channel_id'].'</a>';
                }
                if (isset($row['channel'])) {
                    $row['channel'] = $this->channelListHelper->getChannelLabel($row['channel']);
                }
            }

            $event->setData($data);
        }
    }

    /**
     * @return array
     */
    private function getDncReasons()
    {
        return [
            0 => $this->translator->trans('mautic.lead.report.dnc_contactable'),
            1 => $this->translator->trans('mautic.lead.report.dnc_unsubscribed'),
            2 => $this->translator->trans('mautic.lead.report.dnc_bounced'),
            3 => $this->translator->trans('mautic.lead.report.dnc_manual'),
        ];
    }

    /**
     * @param int $reasonId
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    private function getDncReasonLabel($reasonId)
    {
        if (isset($this->getDncReasons()[$reasonId])) {
            return $this->getDncReasons()[$reasonId];
        }

        throw new \UnexpectedValueException("There is no DNC reason with ID '{$reasonId}'");
    }
}
