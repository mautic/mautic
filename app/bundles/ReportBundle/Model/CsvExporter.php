<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvExporter
{
    public function __construct(
        protected FormatterHelper $formatterHelper,
        private CoreParametersHelper $coreParametersHelper,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @param resource $handle
     * @param int      $page
     */
    public function export(ReportDataResult $reportDataResult, $handle, $page = 1): void
    {
        if (1 === $page) {
            $this->putHeader($reportDataResult, $handle);
        }

        foreach ($reportDataResult->getData() as $data) {
            $row = [];
            foreach ($data as $k => $v) {
                $type       = $reportDataResult->getType($k);
                $typeString = 'string' !== $type;
                $row[]      = $typeString ? $this->formatterHelper->_($v, $type, true) : $v;
            }
            $this->putRow($handle, $row);
        }

        if ($reportDataResult->isLastPage()) {
            $totalsRow = $reportDataResult->getTotalsToExport($this->formatterHelper);

            if (!empty($totalsRow)) {
                $this->putTotals($totalsRow, $handle);
            }
        }
    }

    /**
     * @param resource $handle
     */
    public function putHeader(ReportDataResult $reportDataResult, $handle): void
    {
        $this->putRow($handle, $reportDataResult->getHeaders());
    }

    /**
     * @param array<string> $totals
     * @param resource      $handle
     */
    public function putTotals(array $totals, $handle): void
    {
        // Put label if the first item is empty
        $key = array_key_first($totals);

        if (empty($totals[$key])) {
            $totals[$key] = $this->translator->trans('mautic.report.report.groupby.totals');
        }

        $this->putRow($handle, $totals);
    }

    /**
     * @param resource $handle
     */
    private function putRow($handle, array $row): void
    {
        if ($this->coreParametersHelper->get('csv_always_enclose')) {
            fputs($handle, '"'.implode('","', $row).'"'."\n");
        } else {
            fputcsv($handle, $row);
        }
    }
}
