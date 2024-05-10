<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use Mautic\LeadBundle\Entity\Lead;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides several functions for export-related tasks,
 * like exporting to CSV or Excel.
 */
class ExportHelper
{
    public const EXPORT_TYPE_EXCEL = 'xlsx';

    public const EXPORT_TYPE_CSV   = 'csv';

    public function __construct(
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
        private FilePathResolver $filePathResolver
    ) {
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
     * @param array|\Iterator   $data
     * @param array<int,string> $headerRow
     *
     * @throws Exception
     */
    public function exportDataAs($data, string $type, string $filename, array $headerRow = []): StreamedResponse
    {
        if (is_array($data)) {
            $data = new \ArrayIterator($data);
        }

        if (!$data->valid()) {
            throw new \Exception('No or invalid data given');
        }

        if (self::EXPORT_TYPE_EXCEL === $type) {
            return $this->exportAsExcel($data, $filename, $headerRow);
        }

        if (self::EXPORT_TYPE_CSV === $type) {
            return $this->exportAsCsv($data, $filename, $headerRow);
        }

        throw new \InvalidArgumentException($this->translator->trans('mautic.error.invalid.specific.export.type', ['%type%' => $type, '%expected_type%' => self::EXPORT_TYPE_EXCEL]));
    }

    public function exportDataIntoFile(IteratorExportDataModel $data, string $type, string $fileName): string
    {
        if (!$data->valid()) {
            throw new \Exception('No or invalid data given');
        }

        if (self::EXPORT_TYPE_CSV === $type) {
            return $this->exportAsCsvIntoFile($data, $fileName);
        }

        throw new \InvalidArgumentException($this->translator->trans('mautic.error.invalid.specific.export.type', ['%type%' => $type, '%expected_type%' => self::EXPORT_TYPE_CSV]));
    }

    public function zipFile(string $filePath, string $fileName): string
    {
        $zipFilePath = str_replace('.csv', '.zip', $filePath);
        $zipArchive  = new \ZipArchive();

        if (true === $zipArchive->open($zipFilePath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE)) {
            $zipArchive->addFile($filePath, $fileName);
            $zipArchive->close();
            $this->filePathResolver->delete($filePath);

            return $zipFilePath;
        }

        throw new FilePathException("Could not create zip archive at $zipFilePath.");
    }

    /**
     * @param array<int,string> $headerRow
     *
     * @throws Exception
     */
    private function exportAsExcel(\Iterator $data, string $filename, array $headerRow = []): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename, $headerRow);

        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->setPreCalculateFormulas(false);

        $response = new StreamedResponse(
            function () use ($objWriter): void {
                $objWriter->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * @param array<int,string> $headerRow
     */
    private function addHeaderToSheet(Spreadsheet $spreadsheet, array $headerRow): void
    {
        $spreadsheet->getActiveSheet()->fromArray($headerRow);
    }

    /**
     * @param array<int,string> $headerRow
     */
    private function getSpreadsheetGeneric(\Iterator $data, string $filename, array $headerRow = []): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($filename);
        $spreadsheet->createSheet();

        $rowCount = 2;
        foreach ($data as $key => $row) {
            // Build the header row if defined
            if (0 === $key) {
                $this->addHeaderToSheet($spreadsheet, empty($headerRow) ? array_keys($row) : $headerRow);
            }

            $spreadsheet->getActiveSheet()->fromArray($row, null, "A{$rowCount}");

            // Increment row
            ++$rowCount;
        }

        return $spreadsheet;
    }

    /**
     * @param array<int,string> $headerRow
     */
    private function exportAsCsv(\Iterator $data, string $filename, array $headerRow = []): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheetGeneric($data, $filename, $headerRow);
        $objWriter   = new Csv($spreadsheet);
        $objWriter->setPreCalculateFormulas(false);
        // For UTF-8 support
        $objWriter->setUseBOM(true);

        $response = new StreamedResponse(
            function () use ($objWriter): void {
                $objWriter->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * @param \Iterator<mixed> $data
     */
    private function exportAsCsvIntoFile(\Iterator $data, string $fileName): string
    {
        $filePath  = $this->getValidContactExportFileName($fileName);
        $handler   = @fopen($filePath, 'ab+');
        $headerSet = false;

        foreach ($data as $row) {
            if (!$headerSet) {
                fputcsv($handler, array_keys($row));
                $headerSet = true;
            }

            fputcsv($handler, $row);
        }

        fclose($handler);

        return $filePath;
    }

    private function getValidContactExportFileName(string $fileName): string
    {
        $contactExportDir = $this->coreParametersHelper->get('contact_export_dir');
        $this->filePathResolver->createDirectory($contactExportDir);
        $filePath     = $contactExportDir.'/'.$fileName;
        $fileName     = (string) pathinfo($filePath, PATHINFO_FILENAME);
        $extension    = (string) pathinfo($filePath, PATHINFO_EXTENSION);
        $originalName = $fileName;
        $i            = 1;

        while (file_exists($filePath)) {
            $fileName = $originalName.'_'.$i;
            $filePath = $contactExportDir.'/'.$fileName.'.'.$extension;
            ++$i;
        }

        return $filePath;
    }

    /**
     * @return array<string, string>
     */
    public function parseLeadToExport(Lead $lead): array
    {
        $leadExport = $lead->getProfileFields();

        $stage               = $lead->getStage();
        $leadExport['stage'] = $stage ? $stage->getName() : null;

        return $leadExport;
    }

    public function getExportFilename(string $objectName): string
    {
        $date = (new DateTimeHelper())->toLocalString();

        return str_replace(' ', '_', $date).'_'.InputHelper::alphanum($objectName, false, '_');
    }
}
