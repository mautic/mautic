<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportNormalizeSubscriber implements EventSubscriberInterface
{
    private CompanyModel $companyModel;

    private LeadModel $leadModel;

    public function __construct(LeadModel $leadModel, CompanyModel $companyModel)
    {
        $this->leadModel    = $leadModel;
        $this->companyModel = $companyModel;
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_DISPLAY => ['onReportDisplay', 0],
        ];
    }

    public function onReportDisplay(ReportDataEvent $event): void
    {
        if (!$this->useContactOrCompanyColumn($event->getReport()->getColumns())) {
            return;
        }

        $fields = array_merge($this->leadModel->getRepository()->getCustomFieldList('lead')[0], $this->companyModel->getRepository()->getCustomFieldList('company')[0]);
        $rows   = $event->getData();
        foreach ($rows as $key => $row) {
            foreach ($row as $key2 => $value) {
                if (isset($fields[$key2])) {
                    $rows[$key][$key2] = CustomFieldValueHelper::normalize($value, $fields[$key2]['type'] ?? null, $fields[$key2]['properties'] ?? []);
                }
            }
        }
        $event->setData($rows);
    }

    /**
     * @param array<string> $columns
     */
    protected function useContactOrCompanyColumn(array $columns): bool
    {
        foreach ($columns as $column) {
            if (0 === strpos($column, 'l.') || 0 === strpos($column, 'comp.')) {
                return true;
            }
        }

        return false;
    }
}
