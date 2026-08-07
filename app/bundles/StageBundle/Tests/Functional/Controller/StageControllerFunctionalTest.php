<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\Request;

final class StageControllerFunctionalTest extends MauticMysqlTestCase
{
    private const COUNT_SQL_PREFIX    = 'SELECT COUNT(*) FROM ';

    private const MERGE_TEST_LOG_DATE = '2026-01-01 00:00:00';

    public function testStageMenuString(): void
    {
        $this->createStage('Menu test stage');
        $this->em->flush();

        $stage = $this->client->request(Request::METHOD_GET, '/s/stages');
        self::assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $stageMenuString = $stage->filterXPath('//a[@id="mautic_stage_index"]');
        $this->assertStringContainsString('Stages', $stageMenuString->text());
    }

    public function testStageWithProject(): void
    {
        $stage = new Stage();
        $stage->setName('test');
        $this->em->persist($stage);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/stages/edit/'.$stage->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['stage[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedStage = $this->em->find(Stage::class, $stage->getId());
        $this->assertInstanceOf(Stage::class, $savedStage);
        $this->assertSame($project->getId(), $savedStage->getProjects()->first()->getId());
    }

    public function testStageMergeMovesContactsAndStageLogs(): void
    {
        $primaryStage = $this->createStage('Primary stage');
        $mergedStage  = $this->createStage('Merged stage');

        $contact = new Lead();
        $contact->setEmail('stage-merge-test@example.com');
        $contact->setStage($mergedStage);

        $this->em->persist($contact);
        $this->em->flush();

        $primaryStageId      = $primaryStage->getId();
        $mergedStageId       = $mergedStage->getId();
        $contactId           = $contact->getId();
        $duplicateLogContact = new Lead();
        $duplicateLogContact->setEmail('stage-merge-duplicate-log-test@example.com');
        $duplicateLogContact->setStage($mergedStage);
        $this->em->persist($duplicateLogContact);
        $this->em->flush();
        $duplicateLogContactId = $duplicateLogContact->getId();

        $connection = $this->em->getConnection();
        $connection->insert(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', [
            'stage_id'   => $mergedStageId,
            'lead_id'    => $contactId,
            'date_fired' => self::MERGE_TEST_LOG_DATE,
        ]);
        $connection->insert(MAUTIC_TABLE_PREFIX.'lead_stages_change_log', [
            'lead_id'     => $contactId,
            'stage_id'    => $mergedStageId,
            'event_name'  => 'Stage changed',
            'action_name' => 'Merged stage',
            'date_added'  => self::MERGE_TEST_LOG_DATE,
        ]);
        $connection->insert(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', [
            'stage_id'   => $primaryStageId,
            'lead_id'    => $duplicateLogContactId,
            'date_fired' => self::MERGE_TEST_LOG_DATE,
        ]);
        $connection->insert(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', [
            'stage_id'   => $mergedStageId,
            'lead_id'    => $duplicateLogContactId,
            'date_fired' => '2026-01-02 00:00:00',
        ]);

        /** @var StageModel $stageModel */
        $stageModel = self::getContainer()->get(StageModel::class);
        $stageModel->stageMerge($primaryStage, $mergedStage);
        $this->em->clear();

        $savedContact = $this->em->find(Lead::class, $contactId);
        $this->assertInstanceOf(Lead::class, $savedContact);
        $savedContactStage = $savedContact->getStage();
        $this->assertInstanceOf(Stage::class, $savedContactStage);
        $this->assertSame($primaryStageId, $savedContactStage->getId());
        $this->assertNotInstanceOf(Stage::class, $this->em->find(Stage::class, $mergedStageId));
        $this->assertSame(1, (int) $connection->fetchOne(
            self::COUNT_SQL_PREFIX.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE stage_id = ? AND lead_id = ?',
            [$primaryStageId, $contactId]
        ));
        $this->assertSame(1, (int) $connection->fetchOne(
            self::COUNT_SQL_PREFIX.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE stage_id = ? AND lead_id = ?',
            [$primaryStageId, $duplicateLogContactId]
        ));
        $this->assertSame(1, (int) $connection->fetchOne(
            self::COUNT_SQL_PREFIX.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE stage_id = ? AND lead_id = ?',
            [$primaryStageId, $contactId]
        ));
        $this->assertSame(0, (int) $connection->fetchOne(
            self::COUNT_SQL_PREFIX.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE stage_id = ?',
            [$mergedStageId]
        ));
        $this->assertSame(0, (int) $connection->fetchOne(
            self::COUNT_SQL_PREFIX.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE stage_id = ?',
            [$mergedStageId]
        ));
    }

    public function testStageMergeActionMovesContactAndDeletesMergedStage(): void
    {
        $primaryStage = $this->createStage('Primary stage from form');
        $mergedStage  = $this->createStage('Merged stage from form');

        $contact = new Lead();
        $contact->setEmail('stage-merge-form-test@example.com');
        $contact->setStage($mergedStage);

        $this->em->persist($contact);
        $this->em->flush();

        $primaryStageId = $primaryStage->getId();
        $mergedStageId  = $mergedStage->getId();
        $contactId      = $contact->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/stages/merge/'.$mergedStageId);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Primary stage from form', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Merged stage from form', (string) $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('stage_merge[buttons][save]')->form();
        $form['stage_merge[stage_to_merge]']->setValue((string) $primaryStageId);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->em->clear();

        $savedContact = $this->em->find(Lead::class, $contactId);
        $this->assertInstanceOf(Lead::class, $savedContact);
        $savedContactStage = $savedContact->getStage();
        $this->assertInstanceOf(Stage::class, $savedContactStage);
        $this->assertSame($primaryStageId, $savedContactStage->getId());
        $this->assertNotInstanceOf(Stage::class, $this->em->find(Stage::class, $mergedStageId));
    }

    private function createStage(string $name): Stage
    {
        $stage = new Stage();
        $stage->setName($name);
        $this->em->persist($stage);

        return $stage;
    }
}
