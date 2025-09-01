<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Validator;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Tests\Functional\Controller\CampaignControllerTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;

final class OrphanEventsValidationFunctionalTest extends MauticMysqlTestCase
{
    use CampaignControllerTrait;

    private const ORPHAN_EVENTS_ERROR_MESSAGE =
        'One or more events are orphaned and must be linked to a node before proceeding';

    public function testCampaignWithConnectedEventsShouldSaveSuccessfully(): void
    {
        $campaign = $this->setupCampaignWithConnectedEvent();
        $version  = $campaign->getVersion();

        // Campaign with properly connected events should save without validation errors
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    public function testCampaignWithOrphanEventsShouldFailValidation(): void
    {
        $campaign = $this->setupCampaignWithOrphanEvent();

        $this->submitFormAndExpectValidationError($campaign);
    }

    public function testCampaignWithMixedConnectedAndOrphanEventsShouldFailValidation(): void
    {
        $campaign = $this->setupCampaignWithMixedEvents();

        $this->submitFormAndExpectValidationError($campaign);
    }

    public function testCampaignWithChainedEventsShouldSaveSuccessfully(): void
    {
        $campaign = $this->setupCampaignWithChainedEvents();
        $version  = $campaign->getVersion();

        // Campaign with properly chained events should save without validation errors
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    private function setupCampaignWithConnectedEvent(): Campaign
    {
        $campaign = $this->createBaseCampaign();

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send Welcome Email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setProperties([]);
        $this->em->persist($event);
        $this->em->flush();

        // Connect the event properly to prevent orphan validation
        $canvasSettings = $this->createCanvasSettings($event->getId());
        $campaign->setCanvasSettings($canvasSettings);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        return $campaign;
    }

    private function setupCampaignWithOrphanEvent(): Campaign
    {
        $campaign = $this->createBaseCampaign();

        // Create an event but don't connect it via canvas settings (orphan)
        $orphanEvent = new Event();
        $orphanEvent->setCampaign($campaign);
        $orphanEvent->setName('Orphan Email');
        $orphanEvent->setType('email.send');
        $orphanEvent->setEventType('action');
        $orphanEvent->setProperties([]);
        $this->em->persist($orphanEvent);
        $this->em->flush();

        // Set up canvas settings with the orphan event in nodes but not in connections
        $canvasSettings = [
            'nodes' => [
                [
                    'id'        => 'lists',
                    'positionX' => 100,
                    'positionY' => 100,
                ],
                [
                    'id'        => $orphanEvent->getId(),
                    'positionX' => 300,
                    'positionY' => 100,
                ],
            ],
            'connections' => [], // No connections - makes it an orphan
        ];
        $campaign->setCanvasSettings($canvasSettings);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        return $campaign;
    }

    private function setupCampaignWithMixedEvents(): Campaign
    {
        $campaign = $this->createBaseCampaign();

        // Create a connected event
        $connectedEvent = new Event();
        $connectedEvent->setCampaign($campaign);
        $connectedEvent->setName('Connected Email');
        $connectedEvent->setType('email.send');
        $connectedEvent->setEventType('action');
        $connectedEvent->setProperties([]);
        $this->em->persist($connectedEvent);

        // Create an orphan event
        $orphanEvent = new Event();
        $orphanEvent->setCampaign($campaign);
        $orphanEvent->setName('Orphan Email');
        $orphanEvent->setType('email.send');
        $orphanEvent->setEventType('action');
        $orphanEvent->setProperties([]);
        $this->em->persist($orphanEvent);

        $this->em->flush();

        // Set up canvas settings with both events in nodes but only one in connections
        $canvasSettings = [
            'nodes' => [
                [
                    'id'        => 'lists',
                    'positionX' => 100,
                    'positionY' => 100,
                ],
                [
                    'id'        => $connectedEvent->getId(),
                    'positionX' => 300,
                    'positionY' => 100,
                ],
                [
                    'id'        => $orphanEvent->getId(),
                    'positionX' => 500,
                    'positionY' => 100,
                ],
            ],
            'connections' => [
                [
                    'sourceId' => 'lists',
                    'targetId' => $connectedEvent->getId(),
                    'anchors'  => [
                        'source' => 'leadsource',
                        'target' => 'top',
                    ],
                ],
                // Note: orphanEvent is not connected, making it an orphan
            ],
        ];
        $campaign->setCanvasSettings($canvasSettings);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        return $campaign;
    }

    private function setupCampaignWithChainedEvents(): Campaign
    {
        $campaign = $this->createBaseCampaign();

        // Create a condition event
        $conditionEvent = new Event();
        $conditionEvent->setCampaign($campaign);
        $conditionEvent->setName('Check Country');
        $conditionEvent->setType('lead.field_value');
        $conditionEvent->setEventType('condition');
        $conditionEvent->setProperties([
            'field'    => 'country',
            'operator' => '=',
            'value'    => 'United States',
        ]);
        $this->em->persist($conditionEvent);

        // Create an action event chained to the condition using setParent()
        $actionEvent = new Event();
        $actionEvent->setCampaign($campaign);
        $actionEvent->setParent($conditionEvent);
        $actionEvent->setName('Send US Email');
        $actionEvent->setType('email.send');
        $actionEvent->setEventType('action');
        $actionEvent->setProperties([]);
        $this->em->persist($actionEvent);

        $this->em->flush();

        // Connect both events via canvas settings
        $canvasSettings = $this->createCanvasSettingsWithMultipleEvents(
            $conditionEvent->getId(),
            $actionEvent->getId()
        );
        $campaign->setCanvasSettings($canvasSettings);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        return $campaign;
    }

    private function createBaseCampaign(): Campaign
    {
        $leadList = new LeadList();
        $leadList->setName('Test list');
        $leadList->setAlias('test-list');
        $leadList->setPublicName('Test list');
        $this->em->persist($leadList);

        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $campaign->setIsPublished(true);
        $campaign->setPublishUp(new \DateTime());
        $campaign->addList($leadList);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function submitFormAndExpectValidationError(Campaign $campaign): void
    {
        $version = $campaign->getVersion();

        // Submit the form and expect validation to prevent save (version should not increment)
        $crawler    = $this->refreshPage($campaign);
        $form       = $crawler->selectButton('Save')->form();
        $newCrawler = $this->client->submit($form);

        Assert::assertTrue($this->client->getResponse()->isOk());

        // Verify the validation error message is displayed
        Assert::assertStringContainsString(
            self::ORPHAN_EVENTS_ERROR_MESSAGE,
            $newCrawler->text()
        );

        // Verify the campaign version was not incremented (save was prevented)
        $this->em->clear();
        $campaign = $this->em->find(Campaign::class, $campaign->getId());
        Assert::assertSame($version, $campaign->getVersion());
    }
}
