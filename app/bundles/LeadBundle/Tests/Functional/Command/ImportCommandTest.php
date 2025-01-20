<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Model\ImportModel;
use Symfony\Component\HttpFoundation\Request;

class ImportCommandTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @var array|string[][]
     */
    private array $csvRows = [
        ['email', 'firstname', 'lastname'],
        ['john1@doe.email', 'John', 'Doe1'],
        ['john2@doe.email', 'John', 'Doe2'],
        ['john3@doe.email', 'John', 'Doe3'],
    ];

    /**
     * @var string[]
     */
    private array $csvFiles = [];

    protected function beforeTearDown(): void
    {
        foreach ($this->csvFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testImportNotification(): void
    {
        // Create contact import for ghosting.
        $this->createCsvContactImport(Import::IN_PROGRESS);
        $this->createCsvContactImport(Import::IN_PROGRESS);

        // Create another import to be run
        $import = $this->createCsvContactImport();

        // Run command to import CSV.
        $this->testSymfonyCommand('mautic:import', ['-e' => 'test', '-i' => $import->getId(), '--limit' => 10000]);

        // See the notifications.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/import');
        $html    = $crawler->filterXPath('//div[contains(@id, "notifications")]')->html();
        $this->assertStringContainsString('Import failed. Reason: The import hasn\'t been updated in 2 hours by the background job. It\'s considered failed', $html, $html);
    }

    private function generateSmallCSV(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_').'.csv';
        $file    = fopen($tmpFile, 'wb');

        foreach ($this->csvRows as $line) {
            fputcsv($file, $line);
        }

        fclose($file);

        $this->csvFiles[$tmpFile] = $tmpFile;

        return $tmpFile;
    }

    private function createCsvContactImport(int $status = Import::QUEUED): Import
    {
        $csvFile = $this->generateSmallCSV();

        $now    = new \DateTime();
        $import = new Import();
        $import->setIsPublished(true);
        $import->setDateAdded($now->modify('-4 hours'));
        $import->setDateModified($now->modify('-3 hours'));
        $import->setCreatedBy(1);
        $import->setDir('/tmp');
        $import->setFile(basename($csvFile));
        $import->setOriginalFile(basename($csvFile));
        $import->setLineCount(3);
        $import->setInsertedCount(0);
        $import->setUpdatedCount(0);
        $import->setIgnoredCount(0);
        $import->setStatus($status);
        $import->setObject('lead');
        $properties = [
            'fields'   => [
                'file'      => 'file',
                'email'     => 'email',
                'firstname' => 'firstname',
                'lastname'  => 'lastname',
            ],
            'parser'   => [
                'escape'     => '\\',
                'delimiter'  => ',',
                'enclosure'  => '"',
                'batchlimit' => 100,
            ],
            'headers'  => [
                'file',
                'email',
                'firstname',
                'lastname',
            ],
            'defaults' => [
                'list'  => null,
                'owner' => null,
            ],
        ];
        $import->setProperties($properties);

        /** @var ImportModel $importModel */
        $importModel = static::getContainer()->get('mautic.lead.model.import');
        $importModel->saveEntity($import);

        return $import;
    }
}
