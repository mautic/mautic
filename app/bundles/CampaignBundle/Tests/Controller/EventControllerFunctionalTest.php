<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

final class EventControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider fieldAndValueProvider
     */
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
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));

        $actualEventData = array_filter($responseData['event'], fn ($value) => in_array($value, [
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
    }

    /**
     * @return string[][]
     */
    public static function fieldAndValueProvider(): array
    {
        return [
            'country'  => ['country', 'India'],
            'region'   => ['state', 'Arizona'],
            'timezone' => ['timezone', 'Marigot'],
            'locale'   => ['preferred_locale', 'af'],
        ];
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
        $eventId = $responseData['event']['id'];

        // GET EDIT FORM
        $uri = "/s/campaigns/events/edit/{$eventId}?campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=no&anchorEventType=condition";
        $this->client->xmlHttpRequest('GET', $uri);
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

        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
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
        Assert::assertEquals('marketing', $form['campaignevent[properties][email_type]']->getValue(), 'The default email type should be "marketing"');
    }
}
