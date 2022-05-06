<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use ArrayIterator;
use Iterator;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides several functions for export-related tasks,
 * like exporting to CSV or Excel.
 */
class ExportHelper
{
    public const EXPORT_TYPE_EXCEL = 'xlsx';
    public const EXPORT_TYPE_CSV   = 'csv';

    /** @var TranslatorInterface */
    private $translator;

    private CoreParametersHelper $coreParametersHelper;

    public function __construct(TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->translator          = $translator;
        $this->coreParametersHelper= $coreParametersHelper;
    }

    /**
     * Returns supported export types as an array.
     */
    public function getSupportedExportTypes(): array
    {
        return [
            self::EXPORT_TYPE_CSV,
            self::EXPORT_TYPE_EXCEL,
        ];
    }

    /**
     * Exports data as the given export type. You can get available export types with getSupportedExportTypes().
     *
     * @param array|\Iterator $data
     */
    public function exportDataAs($data, string $type, string $filename): StreamedResponse
    {
        if (is_array($data)) {
            $data = new ArrayIterator($data);
        }

        if (!$data->valid()) {
            throw new \Exception('No or invalid data given');
        }

        switch ($type) {
            case self::EXPORT_TYPE_CSV:
                return $this->exportAsCsv($data, $filename);

            case self::EXPORT_TYPE_EXCEL:
                return $this->exportAsExcel($data, $filename);

            default:
                throw new \InvalidArgumentException($this->translator->trans('mautic.error.invalid.export.type', ['%type%' => $type]));
        }
    }

    private function getSpreadsheetGeneric(Iterator $data, string $filename): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($filename);
        $spreadsheet->createSheet();

        $rowCount = 2;
        foreach ($data as $key => $row) {
            if (0 === $key) {
                // Build the header row from keys in the current row.
                $spreadsheet->getActiveSheet()->fromArray(array_keys($row), null, 'A1');
            }

            $spreadsheet->getActiveSheet()->fromArray($row, null, "A{$rowCount}");

            // Increment row
            ++$rowCount;
        }

        return $spreadsheet;
    }

    private function exportAsExcel(Iterator $data, string $filename): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename);

        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->setPreCalculateFormulas(false);

        $response = new StreamedResponse(
            function () use ($objWriter) {
                $objWriter->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', 0);
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    private function exportAsCsv(Iterator $data, string $filename): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename);

        $objWriter = new Csv($spreadsheet);
        $objWriter->setPreCalculateFormulas(false);
        // For UTF-8 support
        $objWriter->setUseBOM(true);

        $response = new StreamedResponse(
            function () use ($objWriter) {
                $objWriter->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', 0);
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    public function exportDataIntoFile(IteratorExportDataModel $data, string $type, string $filename): string
    {
        if (!$data->valid()) {
            throw new \Exception('No or invalid data given');
        }

        switch ($type) {
            case self::EXPORT_TYPE_CSV:
                return $this->exportAsCsvIntoFile($data, $filename);

            case self::EXPORT_TYPE_EXCEL:
                return $this->exportAsExcelIntoFile($data, $filename);

            default:
                throw new \InvalidArgumentException($this->translator->trans('mautic.error.invalid.export.type', ['%type%' => $type]));
        }
    }

    /**
     * @param Iterator<mixed> $data
     */
    private function exportAsCsvIntoFile(Iterator $data, string $filename): string
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename);

        $objWriter = new Csv($spreadsheet);
        $objWriter->setPreCalculateFormulas(false);
        // For UTF-8 support
        $objWriter->setUseBOM(true);
        $filePath = $this->coreParametersHelper->get('contact_export_dir').'/'.$filename;

        $objWriter->save($filePath);

        return $filePath;
    }

    /**
     * @param Iterator<mixed> $data
     */
    private function exportAsExcelIntoFile(Iterator $data, string $filename): string
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename);
        $objWriter   = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->setPreCalculateFormulas(false);
        $filePath = $this->coreParametersHelper->get('contact_export_dir').'/'.$filename;

        $objWriter->save($filePath);

        return $filePath;
    }
}
