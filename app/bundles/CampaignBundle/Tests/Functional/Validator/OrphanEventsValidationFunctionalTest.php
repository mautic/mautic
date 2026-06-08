<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Validator;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Tests\Functional\Controller\CampaignControllerTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
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
        $version  = $campaign->getVersion();

        // Submit the form and expect validation to prevent save (version should not increment)
        $this->submitFormExpectingValidationFailure($campaign, $version);
    }

    public function testCampaignWithMixedConnectedAndOrphanEventsShouldFailValidation(): void
    {
        $campaign = $this->setupCampaignWithMixedEvents();
        $version  = $campaign->getVersion();

        // Submit the form and expect validation to prevent save
        $this->submitFormExpectingValidationFailure($campaign, $version);
    }

    public function testCampaignWithChainedEventsShouldSaveSuccessfully(): void
    {
        $campaign = $this->setupCampaignWithChainedEvents();
        $version  = $campaign->getVersion();

        // Campaign with properly chained events should save without validation errors
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    public function testCampaignWithFormAsSourceShouldSaveSuccessfully(): void
    {
        $campaign = $this->setupCampaignWithFormAsSource();
        $version  = $campaign->getVersion();

        // Campaign with form as source and properly connected events should save without validation errors
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    public function testCampaignWithFormAsSourceAndOrphanEventsShouldFailValidation(): void
    {
        $campaign = $this->setupCampaignWithFormAsSourceAndOrphanEvents();
        $version  = $campaign->getVersion();

        // Submit the form and expect validation to prevent save due to orphan events
        $this->submitFormExpectingValidationFailure($campaign, $version);
    }

    private function setupCampaignWithConnectedEvent(): Campaign
    {
        $campaign = $this->createBaseCampaign();
        $this->addConnectedEventToCampaign($campaign);

        return $campaign;
    }

    private function setupCampaignWithOrphanEvent(): Campaign
    {
        $campaign = $this->createBaseCampaign();

        // Create an event but don't connect it via canvas settings (orphan)
        $orphanEvent = $this->createOrphanEvent($campaign);

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
        $orphanEvent = $this->createOrphanEvent($campaign);

        // Create canvas settings where only the first event is connected (second is orphan)
        $baseSettings = $this->createCanvasSettings($connectedEvent->getId());

        // Add the orphan event to nodes but don't connect it
        $canvasSettings            = $baseSettings;
        $canvasSettings['nodes'][] = [
            'id'        => $orphanEvent->getId(),
            'positionX' => 500,
            'positionY' => 100,
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

    private function setupCampaignWithFormAsSource(): Campaign
    {
        $campaign = $this->createBaseCampaignWithForm();
        $this->addConnectedEventToCampaign($campaign, 'forms');

        return $campaign;
    }

    private function setupCampaignWithFormAsSourceAndOrphanEvents(): Campaign
    {
        $campaign = $this->createBaseCampaignWithForm();
        $this->addConnectedEventToCampaign($campaign, 'forms');

        // Reload campaign after addConnectedEventToCampaign() clears the entity manager
        $campaign = $this->em->find(Campaign::class, $campaign->getId());

        // Create an orphan event
        $orphanEvent = $this->createOrphanEvent($campaign);

        // Add orphan event to canvas settings without connections
        $canvasSettings            = $campaign->getCanvasSettings();
        $canvasSettings['nodes'][] = [
            'id'        => $orphanEvent->getId(),
            'positionX' => 500,
            'positionY' => 100,
        ];
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

    private function createBaseCampaignWithForm(): Campaign
    {
        $form = new Form();
        $form->setName('Test form');
        $form->setAlias('test-form');
        $this->em->persist($form);

        $campaign = new Campaign();
        $campaign->setName('Test campaign with form');
        $campaign->setIsPublished(true);
        $campaign->setPublishUp(new \DateTime());
        $campaign->addForm($form);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function submitFormExpectingValidationFailure(Campaign $campaign, int $originalVersion): void
    {
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
        Assert::assertSame($originalVersion, $campaign->getVersion());
    }

    private function addConnectedEventToCampaign(Campaign $campaign, string $sourceType = 'lists'): void
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send Welcome Email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setProperties([]);
        $this->em->persist($event);
        $this->em->flush();

        // Connect the event to source via canvas settings
        $canvasSettings = $this->createCanvasSettings($event->getId(), $sourceType);
        $campaign->setCanvasSettings($canvasSettings);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();
    }

    private function createOrphanEvent(Campaign $campaign): Event
    {
        $orphanEvent = new Event();
        $orphanEvent->setCampaign($campaign);
        $orphanEvent->setName('Orphan Email');
        $orphanEvent->setType('email.send');
        $orphanEvent->setEventType('action');
        $orphanEvent->setProperties([]);
        $this->em->persist($orphanEvent);
        $this->em->flush();

        return $orphanEvent;
    }
}
