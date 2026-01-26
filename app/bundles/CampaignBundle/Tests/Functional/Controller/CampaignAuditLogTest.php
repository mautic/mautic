<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignAuditLogTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testCampaignAuditLog(): void
    {
        // Create a Segment.
        $segment = $this->createSegment('seg1', []);

        $campaign = $this->createCampaign('Audit Log Campaign');
        $campaign->addList($segment);
        $campaign->setIsPublished(true);

        $event = new Event();
        $event->setName('Change points event');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setOrder(1);
        $event->setProperties(['points' => 21]);
        $event->setTriggerMode('date');
        $event->setTriggerDate(new \DateTime('2023-09-27 21:37'));
        $event->setCampaign($campaign);

        $this->em->persist($event);
        $this->em->flush();
        $this->em->clear();

        $campaignId     = $campaign->getId();
        $eventId        = $event->getId();
        $modifiedEvents = []; // Initialize empty for consistency with API approach

        // 2. Update the event through API to test EventController and create audit log.

        // 2.b Get the event edit form.
        $uri = "/s/campaigns/events/edit/{$eventId}?campaignId={$campaignId}&anchor=leadsource";
        $this->client->xmlHttpRequest('GET', $uri, ['modifiedEvents' => json_encode($modifiedEvents)]);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // Update the event.
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[canvasSettings][droppedX]'   => '863',
                'campaignevent[canvasSettings][droppedY]'   => '363',
                'campaignevent[name]'                       => '2 contact points after 1 day',
                'campaignevent[triggerMode]'                => 'interval',
                'campaignevent[triggerDate]'                => '2023-09-27 21:37',
                'campaignevent[triggerInterval]'            => '1',
                'campaignevent[triggerIntervalUnit]'        => 'd',
                'campaignevent[triggerHour]'                => '',
                'campaignevent[triggerRestrictedStartHour]' => '',
                'campaignevent[triggerRestrictedStopHour]'  => '',
                'campaignevent[anchor]'                     => 'no',
                'campaignevent[properties][points]'         => '2',
                'campaignevent[properties][group]'          => '',
                'campaignevent[type]'                       => 'lead.changepoints',
                'campaignevent[eventType]'                  => 'action',
                'campaignevent[anchorEventType]'            => 'condition',
                'campaignevent[campaignId]'                 => $campaignId,
            ]
        );

        $this->setCsrfHeader();
        $formData                   = $form->getPhpValues();
        $formData['modifiedEvents'] = json_encode($modifiedEvents);
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $formData);

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success'], print_r(json_decode($response->getContent(), true), true));

        // 2.c Save campaign through CampaignModel to trigger audit log creation
        $campaignModel = static::getContainer()->get('mautic.campaign.model.campaign');
        $campaign      = $campaignModel->getEntity($campaignId);
        $event         = $this->em->find(Event::class, $eventId);
        $event->setName('2 contact points after 1 day');
        $campaign->addEvent($eventId, $event);
        $campaignModel->saveEntity($campaign);
        $this->em->clear();

        // 3. View the campaign.
        $campaignViewUrl = '/s/campaigns/view/'.$campaignId;
        $this->client->request(Request::METHOD_GET, $campaignViewUrl);
        $this->assertResponseIsSuccessful();

        $translator = static::getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.changelog.event_updated'),
            $this->client->getResponse()->getContent()
        );

        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.changelog.event_updated_details', ['%event_id%' => $eventId]),
            $this->client->getResponse()->getContent()
        );
    }
}
