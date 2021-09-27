<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ImportModel;
use PHPUnit\Framework\Assert;

class ImportControllerFunctionalTest extends MauticMysqlTestCase
{
    private $csvFile;
    private $csvRows = [
        ['file', 'email', 'firstname', 'lastname'],
        ['test1.pdf', 'john1@doe.email', 'John', 'Doe1'],
        ['test2.pdf', 'john2@doe.email', 'John', 'Doe2'],
        ['test3.pdf', 'john3@doe.email', 'John', 'Doe3'],
    ];

    public function testImportCSVWithFileAsHeaderName(): void
    {
        $this->loginUser('admin');
        // Create 'file' field.
        $this->createField('text', 'file');

        // Create contact import.
        $import = $this->createCsvContactImport();

        // Run import command.
        $output = $this->runCommand('mautic:import', ['-e' => 'dev', '--id' => $import->getId(), '--limit' => 10000]);
        Assert::assertStringContainsString(
            '4 lines were processed, 3 items created, 0 items updated, 1 items ignored',
            $output
        );
        $leadCount = $this->em->getRepository(Lead::class)->count(['firstname' => 'John']);
        Assert::assertSame(3, $leadCount);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->generateSmallCSV();
    }

    protected function beforeTearDown(): void
    {
        if (isset($this->csvFile) && file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }
    }

    private function createField(string $type, string $alias): void
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($alias);

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->container->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
    }

    private function createCsvContactImport(): Import
    {
        $now    = new DateTime();
        $import = new Import();
        $import->setIsPublished(true);
        $import->setDateAdded($now);
        $import->setCreatedBy(1);
        $import->setDir('/tmp');
        $import->setFile(basename($this->csvFile));
        $import->setOriginalFile(basename($this->csvFile));
        $import->setLineCount(3);
        $import->setInsertedCount(0);
        $import->setUpdatedCount(0);
        $import->setIgnoredCount(0);
        $import->setStatus(1);
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
                'tags'  => [],
                'owner' => null,
            ],
        ];
        $import->setProperties($properties);

        /** @var ImportModel $importModel */
        $importModel = $this->container->get('mautic.lead.model.import');
        $importModel->saveEntity($import);

        return $import;
    }

    private function generateSmallCSV(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_').'.csv';
        $file    = fopen($tmpFile, 'wb');

        foreach ($this->csvRows as $line) {
            fputcsv($file, $line);
        }

        fclose($file);
        $this->csvFile = $tmpFile;
    }
}
