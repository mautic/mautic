<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;

class ImportControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $csvFile;

    protected function beforeTearDown(): void
    {
        if (isset($this->csvFile) && file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }
    }

    public function testScheduleImport(): void
    {
        $this->generateSmallCSV();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);
        $tagName = 'tag1';
        $tag     = $this->createTag($tagName);

        // Show mapping page.
        $crawler      = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadButton = $crawler->selectButton('Upload');
        $form         = $uploadButton->form();
        $form->setValues([
            'lead_import[file]'       => $this->csvFile,
            'lead_import[batchlimit]' => 100,
            'lead_import[delimiter]'  => ',',
            'lead_import[enclosure]'  => '"',
            'lead_import[escape]'     => '\\',
        ]);
        $html = $this->client->submit($form);

        Assert::assertStringContainsString(
            'Match the columns from the imported file to Mautic\'s contact fields.',
            $html->text(null, false)
        );

        $importButton = $html->selectButton('Import');
        $importForm   = $importButton->form();
        $importForm->setValues([
            'lead_field_import[tags]' => [$tag->getId()],
        ]);
        $this->client->submit($importForm);

        $importData = $this->em->getRepository(Import::class)->findOneBy(['object' => 'lead']);
        Assert::assertInstanceOf(Import::class, $importData);
        $importProperty = $importData->getProperties();
        Assert::assertSame([$tagName], $importProperty['defaults']['tags']);
    }

    /**
     * @return mixed[]
     */
    public static function dataImportCSV(): iterable
    {
        yield [false, '4 lines were processed, 3 items created, 0 items updated, 1 items ignored'];
        yield [true,  '4 lines were processed, 2 items created, 1 items updated, 1 items ignored'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataImportCSV')]
    public function testImportCSV(bool $createLead, string $expectedOutput): void
    {
        $this->generateSmallCSV();

        if ($createLead) {
            $this->createLead('john1@doe.email');
        }

        // Create custom fields
        $this->createField('text', 'file');
        $stateProperties = [
            'list' => [
                ['label' => 'MH', 'value' => 'MH'],
                ['label' => 'MP', 'value' => 'MP'],
            ],
        ];
        $this->createField('select', 'state_from', $stateProperties);

        // Create import entity
        $import = $this->createCsvContactImport();

        // Execute import
        $output = $this->createAndExecuteImport($import);
        Assert::assertStringContainsString($expectedOutput, $output->getDisplay());

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);
        $leadCount      = $leadRepository->count(['firstname' => 'John']);
        Assert::assertSame(3, $leadCount);

        if ($createLead) {
            $lead       = $leadRepository->findOneBy(['email' => 'john1@doe.email']);
            $fieldValue = $lead ? $lead->getFieldValue('state_from') : null;

            // Assert that existing leads are not updated by import
            Assert::assertNull(
                $fieldValue,
                'Existing lead should not be updated with state_from value.'
            );
        }
    }

    public function testImportWithSpecialCharacterTag(): void
    {
        $this->generateSmallCSV();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->client->loginUser($user, 'mautic');

        $tagRepository  = $this->em->getRepository(Tag::class);
        $tagCountBefore = $tagRepository->count([]);

        $tagName = 'R&R';
        $tag     = $this->createTag($tagName);

        $crawler      = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadButton = $crawler->selectButton('Upload');
        $form         = $uploadButton->form();
        $form->setValues([
            'lead_import[file]'       => $this->csvFile,
            'lead_import[batchlimit]' => 100,
            'lead_import[delimiter]'  => ',',
            'lead_import[enclosure]'  => '"',
            'lead_import[escape]'     => '\\',
        ]);
        $html = $this->client->submit($form);

        $importButton = $html->selectButton('Import');
        $importForm   = $importButton->form();
        $importForm->setValues(['lead_field_import[tags]' => [$tag->getId()]]);
        $this->client->submit($importForm);

        $import = $this->em->getRepository(Import::class)->findOneBy(['object' => 'lead']);
        $output = $this->testSymfonyCommand('mautic:import', [
            '-e'      => 'dev',
            '--id'    => $import->getId(),
            '--limit' => 10000,
        ]);

        Assert::assertStringContainsString(
            '4 lines were processed, 3 items created, 0 items updated, 1 items ignored',
            $output->getDisplay()
        );

        $leadRepository = $this->em->getRepository(Lead::class);
        $leads          = $leadRepository->findBy(['firstname' => 'John']);
        Assert::assertCount(3, $leads);

        foreach ($leads as $lead) {
            $leadTags = $lead->getTags();
            Assert::assertCount(1, $leadTags);
            Assert::assertSame($tagName, $leadTags->first()->getTag());
        }

        $tagCountAfter = $tagRepository->count([]);
        Assert::assertSame($tagCountBefore + 1, $tagCountAfter);
        Assert::assertNotNull($tagRepository->findOneBy(['tag' => $tagName]));
    }

    /**
     * @return mixed[]
     */
    public static function dataImportWithInvalidDates(): iterable
    {
        yield [false, '7 lines were processed, 2 items created, 0 items updated, 5 items ignored'];
        yield [true,  '7 lines were processed, 1 items created, 1 items updated, 5 items ignored'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataImportWithInvalidDates')]
    public function testImportWithInvalidDates(bool $createLead, string $expectedOutput): void
    {
        $this->generateSmallCSV([
            ['file', 'email', 'firstname', 'lastname', 'state_from', 'birth_date'],
            ['test1.pdf', 'john1@doe.email', 'John', 'Doe1', 'MP', '2025-08-01 09:05:59'],
            ['test2.pdf', 'john2@doe.email', 'John', 'Doe2', 'MP', '2025-07-22 09:05:59'],
            ['test3.pdf', 'john3@doe.email', 'John', 'Doe3', 'MP', '01-08-2025'],
            ['test4.pdf', 'john4@doe.email', 'John', 'Doe4', 'MP', '2025/08/01'],
            ['test5.pdf', 'john5@doe.email', 'John', 'Doe5', 'MP', '2025/08/01 09:05:59'],
            ['test6.pdf', 'john6@doe.email', 'John', 'Doe6', 'MP', '2025'],
        ]);

        if ($createLead) {
            $this->createLead('john1@doe.email');
        }

        // Setup fields
        $this->createField('text', 'file');
        $this->createField('select', 'state_from', [
            'list' => [
                ['label' => 'MH', 'value' => 'MH'],
                ['label' => 'MP', 'value' => 'MP'],
            ],
        ]);
        $this->createField('datetime', 'birth_date');

        // Run import
        $import = $this->createCsvContactImport();
        $output = $this->createAndExecuteImport($import);

        Assert::assertStringContainsString($expectedOutput, $output->getDisplay());

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);
        $leadCount      = $leadRepository->count(['firstname' => 'John']);
        Assert::assertSame(2, $leadCount);

        // Recheck import entity for ignored count
        $importEntity = $this->em->getRepository(Import::class)->find($import->getId());
        Assert::assertSame(5, $importEntity->getIgnoredCount());
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createField(string $type, string $alias, array $properties = []): void
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($alias);
        $field->setProperties($properties);

        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
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
        $import->setStatus(1);
        $import->setObject('lead');

        $import->setProperties([
            'fields' => [
                'file'       => 'file',
                'email'      => 'email',
                'firstname'  => 'firstname',
                'lastname'   => 'lastname',
                'state_from' => 'state_from',
                'birth_date' => 'birth_date',
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
                'state_from',
                'birth_date',
            ],
            'defaults' => [
                'list'  => null,
                'tags'  => ['tag1'],
                'owner' => null,
            ],
        ]);

        $this->getContainer()->get('mautic.security.user_token_setter')->setUser($import->getCreatedBy());
        $importModel = static::getContainer()->get('mautic.lead.model.import');
        $importModel->saveEntity($import);

        return $import;
    }

    /**
     * @param array<int, array<int, string>>|null $csvRows
     */
    private function generateSmallCSV(?array $csvRows = null): void
    {
        $csvRows = $csvRows ?: [
            ['file', 'email', 'firstname', 'lastname', 'state_from'],
            ['test1.pdf', 'john1@doe.email', 'John', 'Doe1', 'MP'],
            ['test2.pdf', 'john2@doe.email', 'John', 'Doe2', 'MP'],
            ['test3.pdf', 'john3@doe.email', 'John', 'Doe3', 'MP'],
        ];

        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_').'.csv';
        $file    = fopen($tmpFile, 'wb');
        foreach ($csvRows as $line) {
            CsvHelper::putCsv($file, $line);
        }
        fclose($file);
        $this->csvFile = $tmpFile;
    }

    private function createAndExecuteImport(Import $import): CommandTester
    {
        $crawler      = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadButton = $crawler->selectButton('Upload');
        $form         = $uploadButton->form();
        $form->setValues([
            'lead_import[file]'       => $this->csvFile,
            'lead_import[batchlimit]' => 100,
            'lead_import[delimiter]'  => ',',
            'lead_import[enclosure]'  => '"',
            'lead_import[escape]'     => '\\',
        ]);
        $html = $this->client->submit($form);

        Assert::assertStringContainsString(
            'Match the columns from the imported file to Mautic\'s contact fields.',
            $html->text()
        );

        return $this->testSymfonyCommand('mautic:import', [
            '-e'      => 'dev',
            '--id'    => $import->getId(),
            '--limit' => 10000,
        ]);
    }

    private function createTag(string $tagName): Tag
    {
        $tag = new Tag();
        $tag->setTag($tagName);

        $tagModel = static::getContainer()->get('mautic.lead.model.tag');
        $tagModel->saveEntity($tag);

        return $tag;
    }

    private function createLead(?string $email = null): Lead
    {
        $lead = new Lead();
        if (!empty($email)) {
            $lead->setEmail($email);
        }
        $this->em->persist($lead);

        return $lead;
    }
}
