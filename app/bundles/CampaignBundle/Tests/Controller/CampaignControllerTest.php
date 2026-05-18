<?php

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerTest extends MauticMysqlTestCase
{
    /**
     * Index should return status code 200.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/campaigns');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/campaigns?search=has%3Aresults&tmpl=list');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Get campaign's create page.
     */
    public function testNewActionCampaign(): void
    {
        $this->client->request('GET', '/s/campaigns/new/');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Test cancelling new campaign does not give a 500 error.
     *
     * @see https://github.com/mautic/mautic/issues/11181
     */
    public function testNewActionCampaignCancel(): void
    {
        $crawler = $this->client->request('GET', '/s/campaigns/new/');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="campaign"]')->selectButton('campaign_buttons_cancel')->form();
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
    }

    public function testCampaignWithProject(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $this->em->persist($campaign);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/campaigns/edit/'.$campaign->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['campaign[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedCampaign = $this->em->find(Campaign::class, $campaign->getId());
        Assert::assertSame($project->getId(), $savedCampaign->getProjects()->first()->getId());
    }

    /**
     * Test that campaign events include isRedirectTarget property when editing campaign.
     */
    public function testCampaignEditIncludesIsRedirectTargetProperty(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign for Redirect Target');
        $this->em->persist($campaign);

        // Create a target event (the one being redirected to)
        $targetEvent = new Event();
        $targetEvent->setName('Target Event');
        $targetEvent->setType('email.send');
        $targetEvent->setEventType('action');
        $targetEvent->setCampaign($campaign);
        $this->em->persist($targetEvent);

        // Create a source event that redirects to the target
        $sourceEvent = new Event();
        $sourceEvent->setName('Source Event');
        $sourceEvent->setType('campaign.jump_to_event');
        $sourceEvent->setEventType('action');
        $sourceEvent->setCampaign($campaign);
        $sourceEvent->setDeleted();
        $sourceEvent->setRedirectEvent($targetEvent);
        $this->em->persist($sourceEvent);

        $this->em->flush();
        $this->em->clear();

        // Request the edit page for the campaign
        $this->client->request('GET', '/s/campaigns/edit/'.$campaign->getId());
        $clientResponse = $this->client->getResponse();

        $this->assertResponseIsSuccessful();

        // Check that the campaign elements data includes isRedirectTarget
        $content = $clientResponse->getContent();
        Assert::assertStringContainsString('isRedirectTarget', $content);

        // Verify that the target event is marked as a redirect target
        Assert::assertStringContainsString('"isRedirectTarget": true', $content);
    }
}
