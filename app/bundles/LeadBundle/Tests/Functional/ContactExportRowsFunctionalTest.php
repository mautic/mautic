<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\MergeRecord;
use Mautic\LeadBundle\Model\ContactExportSchedulerModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

final class ContactExportRowsFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['contact_export_in_background'] = false;

        parent::setUp();
    }

    public function testFieldValueRowsMatchProfileFields(): void
    {
        $this->createColourField();
        $stage = $this->createStage('Lead', true);
        $ada   = $this->createContact('Ada', 'r', $stage);
        $bob   = $this->createContact('Bob', null, null);
        $this->em->clear();

        $args      = $this->argsFor([$ada->getId(), $bob->getId()]);
        $leadModel = $this->getLeadModel();
        $expected  = [];
        foreach ($leadModel->getEntities($args)['results'] as $id => $lead) {
            $expected[$id] = ['id' => (int) $lead->getId()] + $lead->getProfileFields();
        }
        $this->em->clear();

        $rows = $leadModel->getEntities(['fieldValuesOnly' => true] + $args)['results'];

        $this->assertSame($expected, $rows);
        $this->assertSame('r', $rows[$ada->getId()]['favourite_colour'], 'Raw value is exported, not the list label.');
    }

    public function testStageIsTheLastColumnForEveryRowShape(): void
    {
        $published   = $this->createContact('Ada', null, $this->createStage('Published', true));
        $unpublished = $this->createContact('Bob', null, $this->createStage('Unpublished', false));
        $none        = $this->createContact('Cat', null, null);
        $this->em->clear();

        $args          = ['fieldValuesOnly' => true, 'withStage' => true] + $this->argsFor([$published->getId(), $unpublished->getId(), $none->getId()]);
        $withCount     = $this->getLeadModel()->getEntities($args)['results'];
        $withoutCount  = $this->getLeadModel()->getEntities(['withTotalCount' => false] + $args);

        foreach ([$withCount, $withoutCount] as $rows) {
            $this->assertSame(
                [$published->getId() => 'Published', $unpublished->getId() => 'Unpublished', $none->getId() => null],
                array_map(fn (array $row) => $row['stage'], $rows)
            );
            foreach ($rows as $row) {
                $this->assertSame('stage', array_key_last($row));
            }
        }
    }

    public function testMergedContactIsReturnedAsFieldValueRow(): void
    {
        $contact = $this->createContact('Ada', null, $this->createStage('Lead', true));
        $oldId   = $contact->getId() + 1000;

        $mergeRecord = new MergeRecord();
        $mergeRecord->setContact($contact);
        $mergeRecord->setMergedId($oldId);
        $mergeRecord->setName('Old Ada');
        $mergeRecord->setDateAdded(new \DateTime());
        $this->em->persist($mergeRecord);
        $this->em->flush();
        $this->em->clear();

        $args = [
            'filter'          => ['string' => '', 'force' => [['column' => 'l.id', 'expr' => 'in', 'value' => [$oldId]]]],
            'fieldValuesOnly' => true,
            'withStage'       => true,
        ];

        $rows = $this->getLeadModel()->getEntities($args);
        $this->assertSame([$contact->getId()], array_keys($rows));
        $this->assertSame($contact->getId(), $rows[$contact->getId()]['id']);
        $this->assertSame('Ada', $rows[$contact->getId()]['firstname']);
        $this->assertSame('Lead', $rows[$contact->getId()]['stage']);

        $wrapped = $this->getLeadModel()->getEntities(['withTotalCount' => true] + $args);
        array_walk_recursive($wrapped, fn ($value) => $this->assertNotInstanceOf(Lead::class, $value));
        foreach ($wrapped['results'] as $row) {
            $this->assertIsArray($row);
        }
    }

    public function testScheduledExportWritesEveryContactOnceAcrossBatches(): void
    {
        $ids = $this->createContacts(250);

        $rows = $this->readScheduledExport(['limit' => 200, 'fileType' => 'csv'] + $this->argsFor($ids));

        $header = array_shift($rows);
        $this->assertSame('id', $header[0]);
        $this->assertSame('stage', end($header));
        $this->assertSame($ids, array_map(fn (array $row) => (int) $row[0], $rows));
    }

    public function testInteractiveCsvHasTheSameColumnsAsScheduledExport(): void
    {
        $this->createColourField();
        $ids = $this->createContacts(3);

        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=csv&ids='.urlencode(json_encode($ids)));
        $this->assertResponseIsSuccessful();
        $content     = (string) $this->client->getInternalResponse()->getContent();
        $interactive = array_map('str_getcsv', explode("\n", trim(preg_replace('/^\xEF\xBB\xBF/', '', $content))));

        $scheduled = $this->readScheduledExport(['limit' => 200, 'fileType' => 'csv'] + $this->argsFor($ids));

        $this->assertSame($scheduled[0], $interactive[0]);
        $this->assertSame(array_reverse($ids), array_map(fn (array $row) => (int) $row[0], array_slice($interactive, 1)));
    }

    /**
     * @param array<int|null> $ids
     *
     * @return array<string, mixed>
     */
    private function argsFor(array $ids): array
    {
        return [
            'filter'         => ['string' => '', 'force' => [['column' => 'l.id', 'expr' => 'in', 'value' => $ids]]],
            'orderBy'        => 'l.id',
            'orderByDir'     => 'ASC',
            'withTotalCount' => true,
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<int, string|null>>
     */
    private function readScheduledExport(array $data): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);

        $scheduler = new ContactExportScheduler();
        $scheduler->setUser($user)
            ->setScheduledDateTime(new \DateTimeImmutable())
            ->setData($data);

        $model   = self::getContainer()->get(ContactExportSchedulerModel::class);
        \assert($model instanceof ContactExportSchedulerModel);
        $zipPath = $model->processAndGetExportFilePath($scheduler);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $csv = (string) $zip->getFromName('contacts_export.csv');
        $zip->close();
        unlink($zipPath);

        return array_map('str_getcsv', explode("\n", trim($csv)));
    }

    /**
     * @return int[]
     */
    private function createContacts(int $count): array
    {
        $contacts = [];
        for ($i = 0; $i < $count; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Contact'.$i);
            $contact->setEmail('contact'.$i.'@example.com');
            $contact->setDateIdentified(new \DateTime());
            $this->em->persist($contact);
            $contacts[] = $contact;
        }
        $this->em->flush();
        $this->em->clear();

        return array_map(fn (Lead $contact) => (int) $contact->getId(), $contacts);
    }

    private function createContact(string $firstname, ?string $colour, ?Stage $stage): Lead
    {
        $contact = new Lead();
        $contact->setFirstname($firstname);
        $contact->setDateIdentified(new \DateTime());
        if ($stage) {
            $contact->setStage($this->em->getReference(Stage::class, $stage->getId()));
        }
        if ($colour) {
            $contact->addUpdatedField('favourite_colour', $colour);
        }
        $this->getLeadModel()->saveEntity($contact);

        return $contact;
    }

    private function createStage(string $name, bool $published): Stage
    {
        $stage = new Stage();
        $stage->setName($name);
        $stage->setIsPublished($published);
        $this->em->persist($stage);
        $this->em->flush();

        return $stage;
    }

    private function createColourField(): void
    {
        $field = new LeadField();
        $field->setType('select');
        $field->setObject('lead');
        $field->setAlias('favourite_colour');
        $field->setName('Favourite Colour');
        $field->setProperties(['list' => [['label' => 'Red', 'value' => 'r'], ['label' => 'Blue', 'value' => 'b']]]);

        $fieldModel = self::getContainer()->get(FieldModel::class);
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);
    }

    private function getLeadModel(): LeadModel
    {
        $leadModel = self::getContainer()->get(LeadModel::class);
        \assert($leadModel instanceof LeadModel);

        return $leadModel;
    }
}
