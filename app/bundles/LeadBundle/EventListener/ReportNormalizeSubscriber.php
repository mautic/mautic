<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportNormalizeSubscriber implements EventSubscriberInterface
{
    private FieldModel $fieldModel;

    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
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

        $fields = $this->fieldModel->getRepository()->getFields();
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
