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

        $this->em->flush();
        $this->em->clear();

        // 1. Start creating a campaign.
        $uri = '/s/campaigns/new';
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();

        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $campaignForm = $crawler->filterXPath('//form[@name="campaign"]')->form();

        // This is new campaign id to be used for later operations.
        $campaignId = $campaignForm->getValues()['campaign[sessionId]'];

        // 1.a Add segment source to campaign.
        $uri = sprintf('/s/campaigns/sources/new/%s?sourceType=lists', $campaignId);
        $this->client->xmlHttpRequest('GET', $uri);
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $crawler = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form    = $crawler->filterXPath('//form[@name="campaign_leadsource"]')->form();
        $form->setValues(
            [
                'campaign_leadsource[lists]'      => [$segment->getId()],
                'campaign_leadsource[sourceType]' => 'lists',
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));

        // 1.b Add a new event
        $uri = sprintf('/s/campaigns/events/new?type=lead.changepoints&eventType=action&campaignId=%s&anchor=no&anchorEventType=condition', $campaignId);
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[canvasSettings][droppedX]'   => '863',
                'campaignevent[canvasSettings][droppedY]'   => '363',
                'campaignevent[name]'                       => '',
                'campaignevent[triggerMode]'                => 'date',
                'campaignevent[triggerDate]'                => '2023-09-27 21:37',
                'campaignevent[triggerInterval]'            => '1',
                'campaignevent[triggerIntervalUnit]'        => 'd',
                'campaignevent[triggerHour]'                => '',
                'campaignevent[triggerRestrictedStartHour]' => '',
                'campaignevent[triggerRestrictedStopHour]'  => '',
                'campaignevent[anchor]'                     => 'no',
                'campaignevent[properties][points]'         => '21',
                'campaignevent[properties][group]'          => '',
                'campaignevent[type]'                       => 'lead.changepoints',
                'campaignevent[eventType]'                  => 'action',
                'campaignevent[anchorEventType]'            => 'condition',
                'campaignevent[campaignId]'                 => $campaignId,
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));

        $eventId = $responseData['event']['id'];

        // 1.c Submit the campaign form.
        $campaignForm->setValues(
            [
                'campaign[name]'         => 'Audit Log Campaign',
                'campaign[description]'  => 'Test campaign to see the logs',
                'campaign[category]'     => '',
                'campaign[isPublished]'  => '1',
                'campaign[allowRestart]' => '0',
                'campaign[publishUp]'    => '',
                'campaign[publishDown]'  => '',
                'campaign[sessionId]'    => $campaignId,
            ]
        );
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($campaignForm->getMethod(), $campaignForm->getUri(), $campaignForm->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $campaignResponseData = json_decode($response->getContent(), true);

        preg_match('/(\d+)$/', $campaignResponseData['route'], $matches);
        $campaignId = $matches[1] ?? null;

        // 2. Update the campaign to create change log.

        // 2.a Edit campaign.
        $uri = '/s/campaigns/edit/'.$campaignId;
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();

        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $campaignForm = $crawler->filterXPath('//form[@name="campaign"]')->form();

        // 2.a.a Get event id.
        $pointsEvent = $this->em->getRepository(Event::class)->findOneBy(['tempId' => $eventId]);
        $eventId     = $pointsEvent->getId();

        // 2.b Get the event edit form.
        $uri = "/s/campaigns/events/edit/{$eventId}?campaignId={$campaignId}&anchor=leadsource";
        $this->client->xmlHttpRequest('GET', $uri);
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

        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success'], print_r(json_decode($response->getContent(), true), true));

        // 2.c Submit the campaign form
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($campaignForm->getMethod(), $campaignForm->getUri(), $campaignForm->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $campaignResponseData = json_decode($response->getContent(), true);

        // 3. View the campaign.
        $this->client->request(Request::METHOD_GET, $campaignResponseData['route']);
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
