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
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ImportControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $csvFile;

    /**
     * @var array|string[][]
     */
    private array $csvRows = [
        ['file', 'email', 'firstname', 'lastname', 'state_from'],
        ['test1.pdf', 'john1@doe.email', 'John', 'Doe1', 'MP'],
        ['test2.pdf', 'john2@doe.email', 'John', 'Doe2', 'MP'],
        ['test3.pdf', 'john3@doe.email', 'John', 'Doe3', 'MP'],
    ];

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

    public function testScheduleImport(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);
        $tagName = 'tag1';
        $tag     = $this->createTag($tagName);
        // Show mapping page.
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
            'Match the columns from the imported file to Mautic\'s contact fields.',
            $html->text(null, false)
        );

        $importButton = $html->selectButton('Import');
        $importForm   = $importButton->form();
        $importForm->setValues(
            [
                'lead_field_import[tags]' => [$tag->getId()],
            ]
        );
        $this->client->submit($importForm);
        $importData = $this->em->getRepository(Import::class)->findOneBy(['object' => 'lead']);
        Assert::assertInstanceOf(Import::class, $importData);
        $importProperty = $importData->getProperties();
        Assert::assertSame([$tagName], $importProperty['defaults']['tags']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('commandOutputStringProvider')]
    public function testImportCSV(bool $createLead, string $expectedOutput, int $created, int $identified, int $imported): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->client->loginUser($user, 'mautic');

        $lead= null;
        if ($createLead) {
            $lead = $this->createLead('john1@doe.email');
        }

        // Create 'file' field.
        $this->createField('text', 'file');
        $stateProperties = [
            'list' => [
                [
                    'label' => 'MH',
                    'value' => 'MH',
                ],
                [
                    'label' => 'MP',
                    'value' => 'MP',
                ],
            ],
        ];
        $this->createField('select', 'state_from', $stateProperties);
        // Create contact import.
        $import = $this->createCsvContactImport();

        // Show mapping page.
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
            'Match the columns from the imported file to Mautic\'s contact fields.',
            $html->text(null, false)
        );

        // Run command to import CSV.
        $output = $this->testSymfonyCommand('mautic:import', [
            '-e'      => 'dev',
            '--id'    => $import->getId(),
            '--limit' => 10000,
        ]);
        Assert::assertStringContainsString(
            $expectedOutput,
            $output->getDisplay()
        );
        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);
        $leadCount      = $leadRepository->count(['firstname' => 'John']);
        Assert::assertSame(3, $leadCount);

        if ($createLead && $lead instanceof Lead) {
            $contact = $leadRepository->getEntity($lead->getId());
            Assert::assertSame('MP', $contact->getFieldValue('state_from'));
        }
    }

    public function testImportWithSpecialCharacterTag(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->client->loginUser($user, 'mautic');

        // Count tags before import
        $tagRepository  = $this->em->getRepository(Tag::class);
        $tagCountBefore = $tagRepository->count([]);

        $tagName = 'R&R';
        $tag     = $this->createTag($tagName);

        // Show mapping page
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

        // Submit import form with special character tag
        $importButton = $html->selectButton('Import');
        $importForm   = $importButton->form();
        $importForm->setValues([
            'lead_field_import[tags]' => [$tag->getId()],
        ]);
        $this->client->submit($importForm);

        // Run import command
        $import = $this->em->getRepository(Import::class)->findOneBy(['object' => 'lead']);
        $output = $this->testSymfonyCommand('mautic:import', [
            '-e'      => 'dev',
            '--id'    => $import->getId(),
            '--limit' => 10000,
        ]);

        // Verify import results
        Assert::assertStringContainsString(
            '4 lines were processed, 3 items created, 0 items updated, 1 items ignored',
            $output->getDisplay()
        );

        // Check if contacts were created with the correct tag
        $leadRepository = $this->em->getRepository(Lead::class);
        $tagRepository  = $this->em->getRepository(Tag::class);

        $leads = $leadRepository->findBy(['firstname' => 'John']);
        Assert::assertCount(3, $leads);

        foreach ($leads as $lead) {
            $leadTags = $lead->getTags();
            Assert::assertCount(1, $leadTags);
            Assert::assertSame($tagName, $leadTags->first()->getTag());
        }

        // Count tags after import
        $tagCountAfter = $tagRepository->count([]);

        // Verify that only one new tag was created
        Assert::assertSame($tagCountBefore + 1, $tagCountAfter, 'Expected only one new tag to be created during import');

        // Verify that the tag with special characters exists
        $specialCharTag = $tagRepository->findOneBy(['tag' => $tagName]);
        Assert::assertNotNull($specialCharTag, 'Tag with special characters should exist');
    }

    /**
     * @return mixed[]
     */
    public static function commandOutputStringProvider(): iterable
    {
        // verify all are created successfully with select field value
        yield [false, '4 lines were processed, 3 items created, 0 items updated, 1 items ignored', 3, 3, 3];
        // verify contact updated successfully with select field value
        yield [true, '4 lines were processed, 2 items created, 1 items updated, 1 items ignored', 2, 3, 3];
    }

    /**
     * @param mixed[] $properties
     */
    private function createField(string $type, string $alias, array $properties = []): void
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($alias);
        $field->setProperties($properties);

        /** @var FieldModel $fieldModel */
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
        $import->setInsertedCount(0);
        $import->setUpdatedCount(0);
        $import->setIgnoredCount(0);
        $import->setStatus(1);
        $import->setObject('lead');
        $properties = [
            'fields' => [
                'file'       => 'file',
                'email'      => 'email',
                'firstname'  => 'firstname',
                'lastname'   => 'lastname',
                'state_from' => 'state_from',
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
            ],
            'defaults' => [
                'list'  => null,
                'tags'  => ['tag1'],
                'owner' => null,
            ],
        ];
        $import->setProperties($properties);
        $this->getContainer()->get('mautic.security.user_token_setter')->setUser($import->getCreatedBy());

        /** @var ImportModel $importModel */
        $importModel = static::getContainer()->get('mautic.lead.model.import');
        $importModel->saveEntity($import);

        return $import;
    }

    private function generateSmallCSV(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_').'.csv';
        $file    = fopen($tmpFile, 'wb');

        foreach ($this->csvRows as $line) {
            CsvHelper::putCsv($file, $line);
        }

        fclose($file);
        $this->csvFile = $tmpFile;
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
