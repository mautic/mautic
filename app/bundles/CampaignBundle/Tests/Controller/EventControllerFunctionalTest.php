<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class EventControllerFunctionalTest extends MauticMysqlTestCase
{
    #[DataProvider('fieldAndValueProvider')]
    public function testCreateContactConditionOnStateField(string $field, string $value): void
    {
        // Fetch the campaign condition form.
        $uri = '/s/campaigns/events/new?type=lead.field_value&eventType=condition&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=leadsource&anchorEventType=source';
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[anchor]'               => 'leadsource',
                'campaignevent[properties][field]'    => $field,
                'campaignevent[properties][operator]' => '=',
                'campaignevent[properties][value]'    => $value,
                'campaignevent[type]'                 => 'lead.field_value',
                'campaignevent[eventType]'            => 'condition',
                'campaignevent[anchorEventType]'      => 'source',
                'campaignevent[campaignId]'           => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));

        $actualEventData = array_filter($responseData['event'], fn ($value): bool => in_array($value, [
            'name',
            'type',
            'eventType',
            'anchor',
            'anchorEventType',
        ]), ARRAY_FILTER_USE_KEY);
        $expectedEventData = [
            'name'            => 'Contact field value',
            'type'            => 'lead.field_value',
            'eventType'       => 'condition',
            'anchor'          => 'leadsource',
            'anchorEventType' => 'source',
        ];

        $this->assertSame($expectedEventData, $actualEventData);
        $this->assertSame('condition', $responseData['eventType']);
        $this->assertSame('campaignEvent', $responseData['mauticContent']);
        $this->assertSame(1, $responseData['closeModal']);
        $this->assertTrue($responseData['formSubmitted'], $response->getContent());
    }

    /**
     * @return \Iterator<(int|string), array<string>>
     */
    public static function fieldAndValueProvider(): \Iterator
    {
        yield 'country' => ['country', 'India'];
        yield 'region' => ['state', 'Arizona'];
        yield 'timezone' => ['timezone', 'Marigot'];
        yield 'locale' => ['preferred_locale', 'af'];
    }

    public function testActionAtSpecificTimeWorkflow(): void
    {
        $uri = '/s/campaigns/events/new?type=lead.changepoints&eventType=action&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=no&anchorEventType=condition';
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
                'campaignevent[campaignId]'                 => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));

        $this->assertNotEmpty($responseData['eventId']);
        $this->assertNotEmpty($responseData['event']['id']);
        $this->assertEquals($responseData['eventId'], $responseData['event']['id']);
        $this->assertSame('action', $responseData['eventType']);
        $this->assertSame('campaignEvent', $responseData['mauticContent']);
        $this->assertSame('by September 27, 2023 9:37 pm UTC', $responseData['label']);
        $this->assertSame(1, $responseData['closeModal']);
        $this->assertArrayHasKey('eventHtml', $responseData);
        $this->assertArrayNotHasKey('updateHtml', $responseData);
        $eventId        = $responseData['event']['id'];
        $modifiedEvents = $responseData['modifiedEvents'] ?? [];

        // GET EDIT FORM
        $uri = "/s/campaigns/events/edit/{$eventId}?campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=no&anchorEventType=condition";
        $this->client->xmlHttpRequest('GET', $uri, ['modifiedEvents' => json_encode($modifiedEvents)]);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // FILL EDIT FORM
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
                'campaignevent[campaignId]'                 => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $formData                   = $form->getPhpValues();
        $formData['modifiedEvents'] = json_encode($modifiedEvents);
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $formData);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success'], print_r(json_decode($response->getContent(), true), true));

        $this->assertEquals($eventId, $responseData['eventId']);
        $this->assertEquals($eventId, $responseData['event']['id']);
        $this->assertSame('2 contact points after 1 day', $responseData['event']['name']);
        $this->assertSame('action', $responseData['eventType']);
        $this->assertSame('campaignEvent', $responseData['mauticContent']);
        $this->assertSame('within 1 day', $responseData['label']);
        $this->assertSame(1, $responseData['closeModal']);
        $this->assertArrayHasKey('updateHtml', $responseData);
        $this->assertArrayNotHasKey('eventHtml', $responseData);
    }

    public function testCloneWorkflow(): void
    {
        $uri = '/s/campaigns/events/new?type=lead.changepoints&eventType=action&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=no&anchorEventType=condition';
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
                'campaignevent[campaignId]'                 => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        $eventId = $responseData['event']['id'];

        // CLONE EVENT
        $uri = "/s/campaigns/events/clone/{$eventId}?campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775";
        $this->client->xmlHttpRequest('POST', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        $this->assertSame('campaignEventClone', $responseData['mauticContent']);
        $this->assertSame('Adjust contact points', $responseData['eventName']);
        $this->assertSame('New campaign', $responseData['campaignName']);

        // INSERT EVENT
        $uri = "/s/campaigns/events/insert/{$eventId}?campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775";
        $this->client->xmlHttpRequest('POST', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        $this->assertSame('action', $responseData['eventType']);
        $this->assertSame('campaignEvent', $responseData['mauticContent']);
        $this->assertTrue($responseData['clearCloneStorage']);
        $this->assertNotEquals($eventId, $responseData['eventId']);
        $this->assertNotEmpty($responseData['eventHtml']);
        $this->assertArrayHasKey('modifiedEvents', $responseData);
        $this->assertNotEmpty($responseData['modifiedEvents']);
    }

    public function testEmailSendTypeDefaultSetting(): void
    {
        // Fetch the campaign action form.
        $uri = '/s/campaigns/events/new?type=email.send&eventType=action&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=leadsource&anchorEventType=source';
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // Get the form HTML element out of the response
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();

        // Assert the field email_type === "marketing"
        $this->assertSame('marketing', $form['campaignevent[properties][email_type]']->getValue(), 'The default email type should be "marketing"');
    }

    public function testEventsAreNotAccessibleWithXhr(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);

        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/edit/'.$event1->getId().'?campaignId='.$campaign->getId(),
            [],
            [],
            [],
            '{}'
        );

        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertSame('You do not have access to the requested area/action.', $response['error']);
    }

    public function testEventsAreAccessible(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);

        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/edit/'.$event1->getId().'?campaignId='.$campaign->getId(),
            [],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );

        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertSame($event1->getId(), $response['eventId']);
        $this->assertSame($event1->getName(), $response['event']['name']);
        $this->assertFalse($response['formSubmitted'], $this->client->getResponse()->getContent());
    }

    public function testEventsAreDeleted(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);

        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/delete/'.$event1->getId(),
            [
                'modifiedEvents' => json_encode([
                    $event1->getId() => [
                        'id'        => $event1->getId(),
                        'eventType' => $event1->getEventType(),
                        'type'      => $event1->getType(),
                    ],
                ]),
            ],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );

        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertSame(1, $response['success']);

        // Check that the deleted event is in the response
        $eventFound = false;
        foreach ($response['deletedEvents'] as $deletedEvent) {
            if (isset($deletedEvent['id']) && $deletedEvent['id'] === (string) $event1->getId()) {
                $eventFound = true;
                $this->assertArrayHasKey('redirectEvent', $deletedEvent);
                $this->assertNull($deletedEvent['redirectEvent']);
                break;
            }
        }
        $this->assertTrue($eventFound, 'Deleted event not found in response');
    }

    public function testEventsAreDeletedWithRedirectId(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);
        $event2   = $this->createEvent('Event2', $campaign);

        $redirectEventId = $event2->getId();

        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/delete/'.$event1->getId().'?redirectTo='.$redirectEventId,
            [
                'modifiedEvents' => json_encode([$event1->getId() => $event1]),
            ],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertSame(1, $response['success']);

        // Check that the deleted event with redirect ID is properly stored
        $eventFound = false;
        foreach ($response['deletedEvents'] as $deletedEvent) {
            if (isset($deletedEvent['id']) && $deletedEvent['id'] === (string) $event1->getId()) {
                $eventFound = true;
                $this->assertArrayHasKey('redirectEvent', $deletedEvent);
                $this->assertNotNull($deletedEvent['redirectEvent'], 'redirectEvent should not be null');
                break;
            }
        }
        $this->assertTrue($eventFound, 'Deleted event with redirect ID not found in response');
    }

    public function testEventsAreUndeleted(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);
        $event2   = $this->createEvent('Event2', $campaign);

        $redirectEventId = $event2->getId();

        // First delete the event
        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/delete/'.$event1->getId().'?redirectTo='.$redirectEventId,
            [
                'modifiedEvents' => json_encode([$event1->getId() => $event1]),
            ],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );
        $this->assertResponseIsSuccessful();

        $deleteResponse = $this->client->getResponse();
        $deleteResponse = json_decode($deleteResponse->getContent(), true);
        $this->assertSame(1, $deleteResponse['success']);

        // Now undelete the event, passing the deletedEvents from the previous response
        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/undelete/'.$event1->getId().'?campaignId='.$campaign->getId(),
            [
                'modifiedEvents' => json_encode([$event1->getId() => $event1]),
                'deletedEvents'  => json_encode($deleteResponse['deletedEvents']),
            ],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );
        $this->assertResponseIsSuccessful();

        $undeleteResponse = $this->client->getResponse();
        $undeleteResponse = json_decode($undeleteResponse->getContent(), true);
        $this->assertSame(1, $undeleteResponse['success']);

        // Verify the event is no longer in the deletedEvents list
        $eventStillExists = false;
        foreach ($undeleteResponse['deletedEvents'] as $deletedEvent) {
            if (isset($deletedEvent['id']) && $deletedEvent['id'] === (string) $event1->getId()) {
                $eventStillExists = true;
                break;
            }
        }
        $this->assertFalse($eventStillExists, 'Event should no longer be in the deletedEvents list');
    }

    public function testEventsAreDeletedWithRedirectIdInPostRequest(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent('Event1', $campaign);
        $event2   = $this->createEvent('Event2', $campaign);

        $redirectEventId = $event2->getId();

        // Pass the redirectTo parameter in the POST data instead of query parameter
        $this->client->request(
            Request::METHOD_POST,
            '/s/campaigns/events/delete/'.$event1->getId(),
            [
                'modifiedEvents' => json_encode([$event1->getId() => $event1]),
                'redirectTo'     => $redirectEventId,
            ],
            [],
            $this->createAjaxHeaders(),
            '{}'
        );
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertSame(1, $response['success']);

        // Check that the deleted event with redirect ID is properly stored
        $eventFound = false;
        foreach ($response['deletedEvents'] as $deletedEvent) {
            if (isset($deletedEvent['id']) && $deletedEvent['id'] === (string) $event1->getId()) {
                $eventFound = true;
                $this->assertArrayHasKey('redirectEvent', $deletedEvent);
                $this->assertNotNull($deletedEvent['redirectEvent'], 'redirectEvent should not be null');
                break;
            }
        }
        $this->assertTrue($eventFound, 'Deleted event with redirect ID from POST data not found in response');
    }

    public function testScheduledTriggerDateDoesNotShiftWithNonUtcUserTimezone(): void
    {
        $userTimezone = 'Europe/Berlin';
        $wallClock    = '2026-12-15 19:00';

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $user->setTimezone($userTimezone);
        $this->em->persist($user);
        $this->em->flush();
        $this->loginUser($user);

        // loginUser() does not go through CoreSubscriber's interactive login timezone bootstrap.
        $this->client->request(Request::METHOD_GET, '/s/dashboard');
        $this->client->getRequest()->getSession()->set('_timezone', $userTimezone);

        $campaign = $this->createCampaign();
        $event    = $this->createEvent('Scheduled email', $campaign);
        $event->setType('lead.changepoints');
        $this->em->persist($event);
        $this->em->flush();

        $modifiedEvents = $this->submitDateTriggeredEvent($campaign, $event, $wallClock);
        $firstPayload   = $this->extractTriggerDatePayload($modifiedEvents, (string) $event->getId());

        // Re-submit the same wall-clock time (reopen + save) — payload must not drift.
        $modifiedEvents = $this->submitDateTriggeredEvent($campaign, $event, $wallClock, $modifiedEvents);
        $secondPayload  = $this->extractTriggerDatePayload($modifiedEvents, (string) $event->getId());
        $this->assertSame($firstPayload, $secondPayload, 'Event AJAX payload triggerDate shifted between saves');

        // Persist like CampaignController: JSON round-trip of modifiedEvents into setEvents().
        $eventId       = $event->getId();
        $campaignId    = $campaign->getId();
        $roundTripped  = json_decode(json_encode($modifiedEvents, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $campaignModel = self::getContainer()->get(CampaignModel::class);

        $this->em->clear();
        $campaign = $this->em->find(Campaign::class, $campaignId);
        $this->assertInstanceOf(Campaign::class, $campaign);
        $campaignModel->setEvents($campaign, $roundTripped, ['connections' => []], []);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Event::class, $eventId);
        $this->assertInstanceOf(Event::class, $reloaded);
        $this->assertSame(Event::TRIGGER_MODE_DATE, $reloaded->getTriggerMode());
        $reloadedTriggerDate = $reloaded->getTriggerDate();
        $this->assertInstanceOf(\DateTime::class, $reloadedTriggerDate);
        $firstUtc = (clone $reloadedTriggerDate)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        // Second persist cycle: DB → array → DateHelper label formatting → JSON → setEvents.
        // Before the fix, DateHelper mutated triggerDate to system TZ and each save shifted the instant.
        $asArray = $reloaded->convertToArray();
        unset($asArray['campaign'], $asArray['children'], $asArray['parent'], $asArray['log']);
        $dateHelper = self::getContainer()->get(DateHelper::class);
        $dateHelper->toFull($asArray['triggerDate']);
        $roundTripped2 = json_decode(json_encode([$eventId => $asArray], JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $campaign = $this->em->find(Campaign::class, $campaignId);
        $this->assertInstanceOf(Campaign::class, $campaign);
        $campaignModel->setEvents($campaign, $roundTripped2, ['connections' => []], []);
        $this->em->flush();
        $this->em->clear();

        $reloadedAgain = $this->em->find(Event::class, $eventId);
        $this->assertInstanceOf(Event::class, $reloadedAgain);
        $reloadedAgainTriggerDate = $reloadedAgain->getTriggerDate();
        $this->assertInstanceOf(\DateTime::class, $reloadedAgainTriggerDate);
        $secondUtc = (clone $reloadedAgainTriggerDate)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $this->assertSame($firstUtc, $secondUtc, 'Persisted triggerDate shifted after DateHelper formatting + save cycle');
    }

    /**
     * @param array<string|int, mixed> $modifiedEvents
     *
     * @return array<string|int, mixed>
     */
    private function submitDateTriggeredEvent(
        Campaign $campaign,
        Event $event,
        string $triggerDate,
        array $modifiedEvents = [],
    ): array {
        $uri = sprintf(
            '/s/campaigns/events/edit/%d?campaignId=%d',
            $event->getId(),
            $campaign->getId()
        );

        $this->client->xmlHttpRequest('GET', $uri, ['modifiedEvents' => json_encode($modifiedEvents)]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[name]'                => 'Scheduled action',
                'campaignevent[triggerMode]'         => 'date',
                'campaignevent[triggerDate]'         => $triggerDate,
                'campaignevent[triggerInterval]'     => '1',
                'campaignevent[triggerIntervalUnit]' => 'd',
                'campaignevent[properties][points]'  => '1',
                'campaignevent[properties][group]'   => '',
                'campaignevent[type]'                => 'lead.changepoints',
                'campaignevent[eventType]'           => 'action',
                'campaignevent[campaignId]'          => (string) $campaign->getId(),
            ]
        );

        $formData                   = $form->getPhpValues();
        $formData['modifiedEvents'] = json_encode($modifiedEvents);
        $formData['submit']         = '1';
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $formData);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($responseData['success'], print_r($responseData, true));
        $this->assertArrayHasKey('modifiedEvents', $responseData);

        return $responseData['modifiedEvents'];
    }

    /**
     * @param array<string|int, mixed> $modifiedEvents
     *
     * @return array{date: string, timezone: string}
     */
    private function extractTriggerDatePayload(array $modifiedEvents, string $eventId): array
    {
        $this->assertArrayHasKey($eventId, $modifiedEvents);
        $triggerDate = $modifiedEvents[$eventId]['triggerDate'] ?? null;
        $this->assertIsArray($triggerDate);
        $this->assertArrayHasKey('date', $triggerDate);
        $this->assertArrayHasKey('timezone', $triggerDate);

        return [
            'date'     => (string) $triggerDate['date'],
            'timezone' => (string) $triggerDate['timezone'],
        ];
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(string $name, Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setTriggerInterval(1);
        $event->setTriggerMode('immediate');
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }
}
