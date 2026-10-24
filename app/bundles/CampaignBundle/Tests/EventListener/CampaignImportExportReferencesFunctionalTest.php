<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Mautic\StageBundle\Entity\Stage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Imports an exported campaign as another instance would: the entities get new IDs,
 * while unrelated entities with the old IDs exist there.
 */
final class CampaignImportExportReferencesFunctionalTest extends MauticMysqlTestCase
{
    // Importing forms creates their results tables, which ends the transaction
    protected $useCleanupRollback = false;

    public function testImportedEventsReferToTheImportedEntities(): void
    {
        $pages    = [$this->createPage('One'), $this->createPage('Two')];
        $forms    = [$this->createForm('One'), $this->createForm('Two')];
        $segments = [$this->createSegment('One'), $this->createSegment('Two')];
        $group    = $this->createPointGroup();
        $email    = $this->createEmail();
        $dwc      = $this->createDynamicContent();

        $campaign = $this->createCampaign([
            'form.submit'        => ['decision', ['forms' => [$forms[0]->getId(), $forms[1]->getId()]]],
            'page.pagehit'       => ['decision', ['pages' => [$pages[0]->getId(), $pages[1]->getId()]]],
            'dwc.decision'       => ['decision', ['dynamicContent' => $dwc->getId()], 'dynamicContent', $dwc->getId()],
            'lead.pageHit'       => ['condition', ['page' => $pages[1]->getId()]],
            'lead.points'        => ['condition', ['operator' => 'gt', 'score' => 5, 'group' => $group->getId()]],
            'lead.segments'      => ['condition', ['segments' => [$segments[0]->getId()]]],
            'form.field_value'   => ['condition', ['form' => $forms[1]->getId(), 'field' => 'email', 'operator' => '=', 'value' => 'a']],
            'lead.changepoints'  => ['action', ['points' => 5, 'group' => $group->getId()]],
            'lead.changelist'    => ['action', ['addToLists' => [$segments[0]->getId()], 'removeFromLists' => [$segments[1]->getId()]]],
            'email.send.to.user' => ['action', ['useremail' => ['email' => $email->getId()], 'user_id' => [1]]],
        ]);

        $newIds = $this->importAsAnotherInstance($this->exportCampaign($campaign));

        $events = $this->getImportedEvents($newIds[Campaign::ENTITY_NAME][$campaign->getId()]);

        $newPage    = fn (Page $page): int => $newIds[Page::ENTITY_NAME][$page->getId()];
        $newForm    = fn (Form $form): int => $newIds[Form::ENTITY_NAME][$form->getId()];
        $newSegment = fn (LeadList $list): int => $newIds[LeadList::ENTITY_NAME][$list->getId()];
        $newGroup   = $newIds[Group::ENTITY_NAME][$group->getId()];

        $this->assertReference([$newForm($forms[0]), $newForm($forms[1])], $events['form.submit'], 'forms');
        $this->assertReference([$newPage($pages[0]), $newPage($pages[1])], $events['page.pagehit'], 'pages');
        $this->assertReference($newIds[DynamicContent::ENTITY_NAME][$dwc->getId()], $events['dwc.decision'], 'dynamicContent');
        $this->assertSame($newIds[DynamicContent::ENTITY_NAME][$dwc->getId()], (int) $events['dwc.decision']->getChannelId());
        $this->assertReference($newPage($pages[1]), $events['lead.pageHit'], 'page');
        $this->assertReference($newGroup, $events['lead.points'], 'group');
        $this->assertReference([$newSegment($segments[0])], $events['lead.segments'], 'segments');
        $this->assertReference($newForm($forms[1]), $events['form.field_value'], 'form');
        $this->assertReference($newGroup, $events['lead.changepoints'], 'group');
        $this->assertReference([$newSegment($segments[0])], $events['lead.changelist'], 'addToLists');
        $this->assertReference([$newSegment($segments[1])], $events['lead.changelist'], 'removeFromLists');
        $this->assertSame($newIds[Email::ENTITY_NAME][$email->getId()], $events['email.send.to.user']->getProperties()['useremail']['email']);
    }

    public function testImportRemovesReferencesToEntitiesThatWereNotImported(): void
    {
        $segments = [$this->createSegment('One'), $this->createSegment('Two')];

        $campaign = $this->createCampaign([
            'lead.changelist' => ['action', ['addToLists' => [$segments[0]->getId(), $segments[1]->getId()], 'removeFromLists' => []]],
        ]);

        $export = $this->exportCampaign($campaign);
        // The second segment is missing from the export, so the old ID would point to whatever segment has it
        $export[LeadList::ENTITY_NAME] = array_values(array_filter(
            $export[LeadList::ENTITY_NAME],
            fn (array $segment): bool => $segment['id'] === $segments[0]->getId()
        ));

        $newIds = $this->importAsAnotherInstance($export);
        $events = $this->getImportedEvents($newIds[Campaign::ENTITY_NAME][$campaign->getId()]);

        $this->assertReference([$newIds[LeadList::ENTITY_NAME][$segments[0]->getId()]], $events['lead.changelist'], 'addToLists');
    }

    public function testImportFindsTheEntitiesThatAreNotExportedByUuidAndRemovesTheOthers(): void
    {
        $sameStage      = $this->createStage('Same');
        $unrelatedStage = $this->createStage('Unrelated');
        $otherCampaign  = $this->createCampaign([]);

        $campaign = $this->createCampaign([
            'stage.change'           => ['action', ['stage' => $sameStage->getId()]],
            'lead.stages'            => ['condition', ['stages' => [$sameStage->getId(), $unrelatedStage->getId()]]],
            'campaign.addremovelead' => ['action', ['addTo' => [$otherCampaign->getId()], 'removeFrom' => ['this']]],
            'lead.changeowner'       => ['action', ['owner' => 1]],
            'lead.addtocompany'      => ['action', ['company' => 1]],
        ]);

        $export = $this->exportCampaign($campaign);
        // On the target, the stage with the old ID is another stage
        $this->connection->executeStatement('UPDATE '.MAUTIC_TABLE_PREFIX.'stages SET uuid = ? WHERE id = ?', [Uuid::uuid4()->toString(), $unrelatedStage->getId()]);

        $newIds = $this->importAsAnotherInstance($export);
        $events = $this->getImportedEvents($newIds[Campaign::ENTITY_NAME][$campaign->getId()]);

        $this->assertReference($sameStage->getId(), $events['stage.change'], 'stage');
        $this->assertReference([$sameStage->getId()], $events['lead.stages'], 'stages');
        $this->assertReference([$otherCampaign->getId()], $events['campaign.addremovelead'], 'addTo');
        $this->assertReference(['this'], $events['campaign.addremovelead'], 'removeFrom');
        // Companies and users cannot be identified on another instance
        $this->assertReference(null, $events['lead.changeowner'], 'owner');
        $this->assertReference(null, $events['lead.addtocompany'], 'company');
    }

    public function testImportCreatesTheTagsAndCustomFieldsTheEventsUse(): void
    {
        $this->dropColumns('leads', 'imported_custom');
        $field = new LeadField();
        $field->setLabel('Imported custom');
        $field->setAlias('imported_custom');
        $field->setType('text');
        $field->setObject('lead');
        self::getContainer()->get(FieldModel::class)->saveEntity($field);

        $campaign = $this->createCampaign([
            'lead.changetags'  => ['action', ['add_tags' => ['brand-new-tag'], 'remove_tags' => []]],
            'lead.updatelead'  => ['action', ['imported_custom' => 'value', 'firstname' => '']],
            'lead.field_value' => ['condition', ['field' => 'imported_custom', 'operator' => '=', 'value' => 'value']],
        ]);

        $export = $this->exportCampaign($campaign);
        $this->assertSame(['imported_custom'], array_column($export[LeadField::ENTITY_NAME] ?? [], 'alias'));

        $newIds = $this->importAsAnotherInstance($export);
        $events = $this->getImportedEvents($newIds[Campaign::ENTITY_NAME][$campaign->getId()]);

        $this->assertInstanceOf(Tag::class, $this->em->getRepository(Tag::class)->findOneBy(['tag' => 'brand-new-tag']));
        $this->assertReference(['brand-new-tag'], $events['lead.changetags'], 'add_tags');

        // The imported field gets another alias next to the existing one with the same alias
        $importedAlias = $this->em->find(LeadField::class, $newIds[LeadField::ENTITY_NAME][(int) $field->getId()])->getAlias();
        $this->assertNotSame('imported_custom', $importedAlias);
        $this->assertReference($importedAlias, $events['lead.field_value'], 'field');
        $this->assertSame([$importedAlias => 'value', 'firstname' => ''], $events['lead.updatelead']->getProperties()['properties']);
    }

    public function testImportedFormCanBeSubmitted(): void
    {
        $form = new Form();
        $form->setName('Form Submitted');
        $form->setAlias('formsubmitted');
        $form->setFormType('campaign');
        $field = new Field();
        $field->setForm($form);
        $field->setLabel('Email');
        $field->setAlias('email');
        $field->setType('email');
        $form->addField(0, $field);
        $formModel = self::getContainer()->get(FormModel::class);
        $formModel->saveEntity($form);
        $formModel->createTableSchema($form, true, true);

        $campaign = $this->createCampaign([
            'form.submit' => ['decision', ['forms' => [$form->getId()]]],
        ]);

        $newIds  = $this->importAsAnotherInstance($this->exportCampaign($campaign));
        $newForm = $this->em->find(Form::class, $newIds[Form::ENTITY_NAME][$form->getId()]);
        $this->assertInstanceOf(Form::class, $newForm);

        // The form is imported before its fields, the results table still needs their columns
        $table   = MAUTIC_TABLE_PREFIX.'form_results_'.$newForm->getId().'_'.$newForm->getAlias();
        $columns = $this->connection->fetchFirstColumn('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
        $this->assertContains('email', $columns);

        $this->client->request('POST', '/form/submit?formId='.$newForm->getId(), ['mauticform' => [
            'formId'    => $newForm->getId(),
            'formName'  => $newForm->getAlias(),
            'return'    => '',
            'messenger' => 1,
            'email'     => 'imported@example.com',
        ]], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertResponseIsSuccessful();
        $this->assertSame('imported@example.com', $this->connection->fetchOne("SELECT email FROM {$table}"));
    }

    public function testImportedCampaignRunsItsActionsOnTheImportedEntities(): void
    {
        $this->dropColumns('leads', 'run_custom');
        $field = new LeadField();
        $field->setLabel('Run custom');
        $field->setAlias('run_custom');
        $field->setType('text');
        $field->setObject('lead');
        self::getContainer()->get(FieldModel::class)->saveEntity($field);

        $segment       = $this->createSegment('Run');
        $group         = $this->createPointGroup();
        $stage         = $this->createStage('Run');
        $otherCampaign = $this->createCampaign([]);

        $campaign = $this->createCampaign([
            'lead.changelist'        => ['action', ['addToLists' => [$segment->getId()], 'removeFromLists' => []]],
            'lead.changepoints'      => ['action', ['points' => 5, 'group' => $group->getId()]],
            'lead.changetags'        => ['action', ['add_tags' => ['run-tag'], 'remove_tags' => []]],
            'stage.change'           => ['action', ['stage' => $stage->getId()]],
            'lead.updatelead'        => ['action', ['run_custom' => 'done']],
            'campaign.addremovelead' => ['action', ['addTo' => [$otherCampaign->getId()], 'removeFrom' => []]],
        ]);

        $newIds = $this->importAsAnotherInstance($this->exportCampaign($campaign));

        $importedCampaign = $this->em->find(Campaign::class, $newIds[Campaign::ENTITY_NAME][$campaign->getId()]);
        $this->assertInstanceOf(Campaign::class, $importedCampaign);
        $importedCampaign->setIsPublished(true);
        $contact = new Lead();
        $contact->setEmail('runner@example.com');
        $this->em->persist($contact);
        $campaignContact = new CampaignLead();
        $campaignContact->setCampaign($importedCampaign);
        $campaignContact->setLead($contact);
        $campaignContact->setDateAdded(new \DateTime());
        $this->em->persist($campaignContact);
        $this->em->flush();
        $contactId = $contact->getId();

        $output = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $importedCampaign->getId()])->getDisplay();
        $this->assertStringContainsString('6 total events were executed', $output);

        $prefix = MAUTIC_TABLE_PREFIX;

        // The actions use the imported segment and point group, not the ones with the old IDs
        $segments = $this->connection->fetchFirstColumn("SELECT leadlist_id FROM {$prefix}lead_lists_leads WHERE lead_id = ? AND manually_removed = 0", [$contactId]);
        $this->assertSame([(string) $newIds[LeadList::ENTITY_NAME][$segment->getId()]], array_map(strval(...), $segments));
        $scores = $this->connection->fetchAllKeyValue("SELECT group_id, score FROM {$prefix}point_group_contact_score WHERE contact_id = ?", [$contactId]);
        $this->assertSame([$newIds[Group::ENTITY_NAME][$group->getId()] => 5], array_map(intval(...), $scores));

        $tags = $this->connection->fetchFirstColumn("SELECT t.tag FROM {$prefix}lead_tags t JOIN {$prefix}lead_tags_xref x ON x.tag_id = t.id WHERE x.lead_id = ?", [$contactId]);
        $this->assertSame(['run-tag'], $tags);

        // The stage and the other campaign are found by UUID on this instance
        $contactRow = $this->connection->fetchAssociative("SELECT * FROM {$prefix}leads WHERE id = ?", [$contactId]);
        $this->assertSame((string) $stage->getId(), (string) $contactRow['stage_id']);
        $this->assertTrue((bool) $this->connection->fetchOne("SELECT 1 FROM {$prefix}campaign_leads WHERE lead_id = ? AND campaign_id = ? AND manually_removed = 0", [$contactId, $otherCampaign->getId()]));

        // The imported custom field got another alias next to the existing one
        $importedAlias = $this->em->find(LeadField::class, $newIds[LeadField::ENTITY_NAME][(int) $field->getId()])->getAlias();
        $this->assertSame('done', $contactRow[$importedAlias]);
        $this->assertNull($contactRow['run_custom']);
    }

    /**
     * Columns survive the database reset between test runs.
     */
    private function dropColumns(string $table, string $prefix): void
    {
        $table = MAUTIC_TABLE_PREFIX.$table;
        foreach ($this->connection->fetchFirstColumn('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME LIKE ?', [$table, $prefix.'%']) as $column) {
            $this->connection->executeStatement("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }

    /**
     * @param int|list<int|string>|string|null $expected
     */
    private function assertReference(int|array|string|null $expected, Event $event, string $property): void
    {
        $properties = $event->getProperties();

        $this->assertSame($expected, $properties[$property], $event->getType());
        $this->assertSame($expected, $properties['properties'][$property], $event->getType());
    }

    /**
     * @return array<string, mixed>
     */
    private function exportCampaign(Campaign $campaign): array
    {
        $event = new EntityExportEvent(Campaign::ENTITY_NAME, (int) $campaign->getId());
        self::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);

        // The export is shared as JSON
        return json_decode((string) json_encode($event->getEntities()), true);
    }

    /**
     * Gives every exported entity a new UUID, so the import creates them with new IDs next to the original ones.
     *
     * @param array<string, mixed> $export
     *
     * @return array<string, array<int, int>> the new IDs by the original IDs per entity name
     */
    private function importAsAnotherInstance(array $export): array
    {
        $uuids = [];
        foreach ($export as $entityName => &$entities) {
            if ('dependencies' === $entityName) {
                continue;
            }
            foreach ($entities as &$entity) {
                if (isset($entity['uuid'], $entity['id'])) {
                    $entity['uuid']                         = Uuid::uuid4()->toString();
                    $uuids[$entityName][(int) $entity['id']] = $entity['uuid'];
                }
            }
            unset($entity);
        }
        unset($entities);

        $event = new EntityImportEvent(Campaign::ENTITY_NAME, $export, null);
        self::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);
        $this->assertEmpty($event->getStatus()[EntityImportEvent::ERRORS] ?? []);
        $this->em->clear();

        $classes = [
            Campaign::ENTITY_NAME       => Campaign::class,
            DynamicContent::ENTITY_NAME => DynamicContent::class,
            Email::ENTITY_NAME          => Email::class,
            Form::ENTITY_NAME           => Form::class,
            Group::ENTITY_NAME          => Group::class,
            LeadField::ENTITY_NAME      => LeadField::class,
            LeadList::ENTITY_NAME       => LeadList::class,
            Page::ENTITY_NAME           => Page::class,
        ];

        $newIds = [];
        foreach ($uuids as $entityName => $uuidsById) {
            if (!isset($classes[$entityName])) {
                continue;
            }
            foreach ($uuidsById as $originalId => $uuid) {
                $imported = $this->em->getRepository($classes[$entityName])->findOneBy(['uuid' => $uuid]);
                $this->assertNotNull($imported, "{$entityName} {$originalId} was not imported");
                $this->assertNotSame($originalId, $imported->getId());
                $newIds[$entityName][$originalId] = (int) $imported->getId();
            }
        }

        return $newIds;
    }

    /**
     * @return array<string, Event>
     */
    private function getImportedEvents(int $campaignId): array
    {
        $events = [];
        foreach ($this->em->getRepository(Event::class)->findBy(['campaign' => $campaignId]) as $event) {
            $events[$event->getType()] = $event;
        }

        return $events;
    }

    /**
     * @param array<string, array{0: string, 1: array<string, mixed>, 2?: string, 3?: int}> $events
     */
    private function createCampaign(array $events): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Exported campaign');
        $this->em->persist($campaign);

        $order = 0;
        foreach ($events as $type => $config) {
            [$eventType, $values] = $config;

            $event = new Event();
            $event->setCampaign($campaign);
            $event->setName($type);
            $event->setType($type);
            $event->setEventType($eventType);
            $event->setOrder(++$order);
            // The builder stores the values both at the top level and under "properties"
            $event->setProperties($values + ['properties' => $values]);
            if (isset($config[2], $config[3])) {
                $event->setChannel($config[2]);
                $event->setChannelId($config[3]);
            }
            $campaign->addEvent($order, $event);
            $this->em->persist($event);
        }

        $this->em->flush();

        return $campaign;
    }

    private function createPage(string $name): Page
    {
        $page = new Page();
        $page->setTitle("Page {$name}");
        $page->setAlias('page-'.strtolower($name));
        $page->setTemplate('blank');
        $page->setCustomHtml('<html><body>Page</body></html>');

        return $this->save($page);
    }

    private function createForm(string $name): Form
    {
        $form = new Form();
        $form->setName("Form {$name}");
        $form->setAlias('form'.strtolower($name));
        $form->setFormType('campaign');

        return $this->save($form);
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName("Segment {$name}");
        $segment->setPublicName("Segment {$name}");
        $segment->setAlias('segment-'.strtolower($name));

        return $this->save($segment);
    }

    private function createStage(string $name): Stage
    {
        $stage = new Stage();
        $stage->setName("Stage {$name}");
        $stage->setWeight(0);

        return $this->save($stage);
    }

    private function createPointGroup(): Group
    {
        $group = new Group();
        $group->setName('Point group');

        return $this->save($group);
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Email to user');
        $email->setSubject('Email to user');
        $email->setEmailType('template');
        $email->setCustomHtml('<html><body>Email</body></html>');

        return $this->save($email);
    }

    private function createDynamicContent(): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Dynamic content');
        $dynamicContent->setContent('Content');
        $dynamicContent->setIsCampaignBased(true);

        return $this->save($dynamicContent);
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function save(object $entity): object
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}
