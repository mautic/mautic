<?php

namespace Mautic\StageBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\StageBundle\Entity\LeadStageLog;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StageMergeIntegrationTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!defined('MAUTIC_TEST_ENVIRONMENT')) {
            define('MAUTIC_TEST_ENVIRONMENT', true);
        }

        // Configure test client to not follow redirects automatically
        $this->client->followRedirects(false);
    }

    /**
     * Helper method to set entity ID using reflection (for testing purposes)
     */
    private function setId($entity, $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'stages',
            'leads',
            'lead_stages_change_log',
            'stage_lead_action_log',
        ]);
    }

    public function testCompleteMergeWorkflow(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setDescription('Primary stage description');
        $primaryStage->setIsPublished(true);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setDescription('Secondary stage description');
        $secondaryStage->setIsPublished(true);

        $lead1 = new Lead();
        $lead1->setEmail('lead1@example.com');
        $lead1->setStage($secondaryStage);

        $lead2 = new Lead();
        $lead2->setEmail('lead2@example.com');
        $lead2->setStage($secondaryStage);

        $lead3 = new Lead();
        $lead3->setEmail('lead3@example.com');
        $lead3->setStage($primaryStage);

        $this->em->persist($primaryStage);
        $this->em->persist($secondaryStage);
        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->persist($lead3);
        $this->em->flush();

        $primaryStageId = $primaryStage->getId();
        $secondaryStageId = $secondaryStage->getId();

        $this->assertNotNull($model->getEntity($primaryStageId));
        $this->assertNotNull($model->getEntity($secondaryStageId));

        $model->stageMerge($primaryStage, $secondaryStage);

        $this->em->clear();

        $this->assertNotNull($model->getEntity($primaryStageId));
        $this->assertNull($model->getEntity($secondaryStageId));

        $updatedLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $updatedLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $updatedLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $this->assertSame($primaryStageId, $updatedLead1->getStage()->getId());
        $this->assertSame($primaryStageId, $updatedLead2->getStage()->getId());
        $this->assertSame($primaryStageId, $updatedLead3->getStage()->getId());
    }

    public function testMergeWorkflowWithController(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);

        $this->em->persist($primaryStage);
        $this->em->persist($secondaryStage);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form->setValues(['stage_merge[stage_to_merge]' => $primaryStage->getId()]);

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/s/stages', $response->headers->get('Location'));

        $this->client->followRedirect();
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMergeWorkflowWithMultipleStages(): void
    {
        $stages = [];
        for ($i = 1; $i <= 5; $i++) {
            $stage = new Stage();
            $stage->setName("Stage {$i}");
            $stage->setIsPublished(true);
            $this->em->persist($stage);
            $stages[] = $stage;
        }

        $this->em->flush();

        $targetStage = $stages[0];
        $sourceStage = $stages[4];

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$sourceStage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form->setValues(['stage_merge[stage_to_merge]' => $targetStage->getId()]);

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testMergeWorkflowWithLeadsAndLogs(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);

        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $lead->setStage($secondaryStage);

        $log = new LeadStageLog();
        $log->setStage($secondaryStage);
        $log->setLead($lead);
        $log->setDateFired(new \DateTime());

        $change = new StagesChangeLog();
        $change->setLead($lead)
            ->setStage($secondaryStage)
            ->setEventName('test_event')
            ->setActionName('test_action')
            ->setDateAdded(new \DateTime());

        $this->em->persist($primaryStage);
        $this->em->persist($secondaryStage);
        $this->em->persist($lead);
        $this->em->persist($log);
        $this->em->persist($change);
        $this->em->flush();

        $primaryStageId = $primaryStage->getId();
        $secondaryStageId = $secondaryStage->getId();
        $changeId = $change->getId();

        $model->stageMerge($primaryStage, $secondaryStage);

        $this->em->clear();

        $this->assertNull($model->getEntity($secondaryStageId));

        $stageId = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE id = ?', [$changeId]);
        $this->assertEquals($primaryStageId, $stageId);
    }

    public function testMergeWorkflowWithFormValidation(): void
    {
        $stage = new Stage();
        $stage->setName('Test Stage');
        $stage->setIsPublished(true);
        $this->em->persist($stage);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$stage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form->setValues(['stage_merge[stage_to_merge]' => '']);

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMergeWorkflowWithInvalidStageId(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/stages/merge/99999');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testMergeWorkflowWithSameStage(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);

        $this->em->persist($primaryStage);
        $this->em->persist($secondaryStage);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form->setValues(['stage_merge[stage_to_merge]' => $primaryStage->getId()]);

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testMergeWorkflowPreservesDataIntegrity(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setDescription('Primary description');
        $primaryStage->setIsPublished(true);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setDescription('Secondary description');
        $secondaryStage->setIsPublished(true);

        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $lead->setStage($secondaryStage);

        $this->em->persist($primaryStage);
        $this->em->persist($secondaryStage);
        $this->em->persist($lead);
        $this->em->flush();

        $primaryStageId = $primaryStage->getId();
        $primaryName = $primaryStage->getName();
        $primaryDescription = $primaryStage->getDescription();

        $model->stageMerge($primaryStage, $secondaryStage);

        $this->em->clear();

        $preservedStage = $model->getEntity($primaryStageId);
        $this->assertNotNull($preservedStage);
        $this->assertEquals($primaryName, $preservedStage->getName());
        $this->assertEquals($primaryDescription, $preservedStage->getDescription());

        $updatedLead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertSame($primaryStageId, $updatedLead->getStage()->getId());
    }
}
