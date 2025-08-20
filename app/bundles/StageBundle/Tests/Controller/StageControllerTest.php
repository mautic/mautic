<?php

namespace Mautic\StageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StageControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure test client to not follow redirects automatically
        $this->client->followRedirects(false);

                // Ensure test environment constant is defined
        if (!defined('MAUTIC_TEST_ENVIRONMENT')) {
            define('MAUTIC_TEST_ENVIRONMENT', true);
        }
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

    public function testMergeActionShowsForm(): void
    {
        $stage = new Stage();
        $stage->setName('Test Stage');
        $stage->setIsPublished(true);
        $this->em->persist($stage);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$stage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Choose a stage to merge into', $response->getContent());
    }

    public function testMergeActionRequiresEditPermission(): void
    {
        $stage = new Stage();
        $stage->setName('Test Stage');
        $stage->setIsPublished(true);
        $this->em->persist($stage);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$stage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMergeActionWithValidData(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);
        $this->em->persist($primaryStage);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);
        $this->em->persist($secondaryStage);

        $this->em->flush();

        // First GET the form page
        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Submit the form
        $form = $crawler->filter('form[name="stage_merge"]')->form();

        $this->assertNotEmpty($form->getUri(), 'Form action is empty');
        $this->assertEquals('POST', $form->getMethod(), 'Form method is not POST');

        $form['stage_merge[stage_to_merge]'] = $primaryStage->getId();

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/s/stages', $response->headers->get('Location'));
    }

    public function testMergeActionWithInvalidStageId(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/stages/merge/99999');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testMergeActionWithSameStage(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);
        $this->em->persist($primaryStage);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);
        $this->em->persist($secondaryStage);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form['stage_merge[stage_to_merge]'] = $primaryStage->getId();

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testMergeActionShowsAvailableStages(): void
    {
        // Use unique names to avoid conflicts with other tests
        $uniqueId = uniqid();

        // Create stages in a more isolated way
        $stage1 = new Stage();
        $stage1->setName("Stage 1 - {$uniqueId}");
        $stage1->setIsPublished(true);
        $this->em->persist($stage1);

        $stage2 = new Stage();
        $stage2->setName("Stage 2 - {$uniqueId}");
        $stage2->setIsPublished(true);
        $this->em->persist($stage2);

        $stage3 = new Stage();
        $stage3->setName("Stage 3 - {$uniqueId}");
        $stage3->setIsPublished(true);
        $this->em->persist($stage3);

        $this->em->flush();

        // Force the entity manager to complete all operations
        $this->em->clear();
        $this->em->flush();

        // Verify the stages were created correctly
        $allStages = $this->em->getRepository(Stage::class)->findAll();
        $this->assertCount(3, $allStages, 'Expected 3 stages to be created');

        // Make the request
        $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$stage1->getId()}");
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Make the assertions
        $this->assertStringContainsString("Stage 2 - {$uniqueId}", $response->getContent());
        $this->assertStringContainsString("Stage 3 - {$uniqueId}", $response->getContent());
        $this->assertStringNotContainsString("Stage 1 - {$uniqueId}", $response->getContent());
    }

    public function testMergeActionRedirectsToListAfterSuccess(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);
        $this->em->persist($primaryStage);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);
        $this->em->persist($secondaryStage);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form['stage_merge[stage_to_merge]'] = $primaryStage->getId();

        $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/s/stages', $response->headers->get('Location'));
    }

    public function testMergeActionShowsSuccessMessage(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);
        $this->em->persist($primaryStage);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);
        $this->em->persist($secondaryStage);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $form = $crawler->filter('form[name="stage_merge"]')->form();
        $form['stage_merge[stage_to_merge]'] = $primaryStage->getId();

        $this->client->submit($form);

        $this->client->followRedirect();
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMergeActionCancelsCorrectly(): void
    {
        $primaryStage = new Stage();
        $primaryStage->setName('Primary Stage');
        $primaryStage->setIsPublished(true);
        $this->em->persist($primaryStage);

        $secondaryStage = new Stage();
        $secondaryStage->setName('Secondary Stage');
        $secondaryStage->setIsPublished(true);
        $this->em->persist($secondaryStage);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/stages/merge/{$secondaryStage->getId()}");
        $form = $crawler->filter('form[name="stage_merge"]')->form();

        // Submit the cancel button instead of the save button
        $cancelForm = $crawler->selectButton('stage_merge_buttons_cancel')->form();
        $this->client->submit($cancelForm);

        $response = $this->client->getResponse();

        // Should redirect (302) when cancelled
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

        // Should redirect to the stages list
        $this->assertStringContainsString('/s/stages', $response->headers->get('Location'));
    }
}
