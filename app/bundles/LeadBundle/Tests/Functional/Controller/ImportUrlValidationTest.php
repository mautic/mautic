<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ImportModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;

final class ImportUrlValidationTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $csvFile;

    protected function beforeTearDown(): void
    {
        if (isset($this->csvFile) && file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }
    }

    public function testImportRejectsInvalidDataProtocolUrl(): void
    {
        $this->generateCSV();

        // Create URL field
        $field = new LeadField();
        $field->setType('url');
        $field->setObject('lead');
        $field->setAlias('website_url');
        $field->setName('website_url');

        $fieldModel = self::getContainer()->get(FieldModel::class);
        $fieldModel->saveEntity($field);

        // Create Import entity manually (same as working test)
        $import = $this->createCsvContactImport();

        // Execute import
        $output = $this->createAndExecuteImport($import);

        $display = $output->getDisplay();

        Assert::assertStringContainsString(
            '4 lines were processed, 2 items created, 0 items updated, 2 items ignored',
            $display
        );

        $leadRepository = $this->em->getRepository(Lead::class);

        Assert::assertNotNull($leadRepository->findOneBy(['email' => 'ok1@a.com']));
        Assert::assertNotNull($leadRepository->findOneBy(['email' => 'ok2@a.com']));
        Assert::assertNull($leadRepository->findOneBy(['email' => 'bad@a.com']));

        $this->em->refresh($import);

        Assert::assertSame(2, $import->getIgnoredCount());
        Assert::assertSame(2, $import->getInsertedCount());
    }

    private function generateCSV(): void
    {
        $invalidUrl = 'data://text/html;base64,PHNjcmlwdD5hbGVydCgpPC9zY3JpcHQ  +';

        $rows = [
            ['email', 'firstname', 'lastname', 'website_url'],
            ['ok1@a.com', 'John', 'Doe', 'https://valid.com'],
            ['bad@a.com', 'Bad', 'Guy', $invalidUrl],
            ['ok2@a.com', 'Jane', 'Doe', 'https://mautic.org'],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'mautic_import_test_').'.csv';
        $fp  = fopen($tmp, 'wb');

        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }

        fclose($fp);
        $this->csvFile = $tmp;
    }

    private function createCsvContactImport(): Import
    {
        $now    = new \DateTime();
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

        // EXACT same style as working test: define all mappings explicitly
        $properties = [
            'fields' => [
                'email'       => 'email',
                'firstname'   => 'firstname',
                'lastname'    => 'lastname',
                'website_url' => 'website_url',
            ],
            'parser' => [
                'escape'     => '\\',
                'delimiter'  => ',',
                'enclosure'  => '"',
                'batchlimit' => 100,
            ],
            'headers' => [
                'email',
                'firstname',
                'lastname',
                'website_url',
            ],
            'defaults' => [
                'list'  => null,
                'tags'  => [],
                'owner' => null,
            ],
        ];

        $import->setProperties($properties);
        self::getContainer()->get('mautic.security.user_token_setter')->setUser($import->getCreatedBy());

        /** @var ImportModel $importModel */
        $importModel = self::getContainer()->get('mautic.lead.model.import');
        $importModel->saveEntity($import);

        return $import;
    }

    private function createAndExecuteImport(Import $import): CommandTester
    {
        // Show mapping page (same as working example)
        $crawler      = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadButton = $crawler->selectButton('Upload');
        $form         = $uploadButton->form();

        $form->setValues(
            [
                'lead_import[file]'       => $this->csvFile,
                'lead_import[batchlimit]' => 100,
                'lead_import[delimiter]'  => ',',
                'lead_import[enclosure]'  => '"',
                'lead_import[escape]'     => '\\',
            ]
        );

        $html = $this->client->submit($form);

        Assert::assertStringContainsString(
            "Match the columns from the imported file to Mautic's contact fields",
            $html->text()
        );

        // Run import command
        return $this->testSymfonyCommand('mautic:import', [
            '-e'      => 'dev',
            '--id'    => $import->getId(),
            '--limit' => 10000,
        ]);
    }
}
