<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

final class ChangeOwnerTypeFunctionalTest extends MauticMysqlTestCase
{
    private const TEMP_CAMPAIGN_ID = 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775';

    public function testCampaignBuilderWithChangeOwnerActionDoesNotBreakOtherEventLinks(): void
    {
        // Add "Modify contact's tags" action
        $uri = '/s/campaigns/events/new?type=lead.changetags&eventType=action&campaignId='.self::TEMP_CAMPAIGN_ID.'&anchor=leadsource&anchorEventType=source';
        $this->client->xmlHttpRequest('GET', $uri);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues([
            'campaignevent[name]'            => 'Modify contact tags',
            'campaignevent[anchor]'          => 'leadsource',
            'campaignevent[type]'            => 'lead.changetags',
            'campaignevent[eventType]'       => 'action',
            'campaignevent[anchorEventType]' => 'source',
            'campaignevent[campaignId]'      => self::TEMP_CAMPAIGN_ID,
        ]);

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $this->assertResponseIsSuccessful();
        $responseData      = json_decode($this->client->getResponse()->getContent(), true);
        $modifyTagsEventId = $responseData['eventId'] ?? null;
        Assert::assertNotNull($modifyTagsEventId, 'Modify tags event should be created');
        Assert::assertSame(1, $responseData['success']);

        // Add "Update contact owner" action below "Modify contact's tags"
        $uri = '/s/campaigns/events/new?type=lead.changeowner&eventType=action&campaignId='.self::TEMP_CAMPAIGN_ID.'&anchor=yes&anchorEventType=action';
        $this->client->xmlHttpRequest('GET', $uri);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();

        // Verify that the form does not contain a buttons field (the fix)
        $buttonsField = $crawler->filter('[id$="_properties_buttons"]');
        Assert::assertCount(0, $buttonsField, 'The buttons field should not exist in the ChangeOwnerType form');

        // Verify owner field exists
        $ownerField = $crawler->filter('#campaignevent_properties_owner');
        Assert::assertCount(1, $ownerField, 'The owner field should exist in the form');

        $form->setValues([
            'campaignevent[name]'            => 'Update contact owner',
            'campaignevent[anchor]'          => 'yes',
            'campaignevent[type]'            => 'lead.changeowner',
            'campaignevent[eventType]'       => 'action',
            'campaignevent[anchorEventType]' => 'action',
            'campaignevent[campaignId]'      => self::TEMP_CAMPAIGN_ID,
        ]);

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $this->assertResponseIsSuccessful();
        $responseData       = json_decode($this->client->getResponse()->getContent(), true);
        $changeOwnerEventId = $responseData['eventId'] ?? null;
        Assert::assertNotNull($changeOwnerEventId, 'Change owner event should be created');
        Assert::assertSame(1, $responseData['success']);

        // Verify that the first event (Modify contact tags) can still be edited after adding change owner
        $modifiedEvents = [
            $modifyTagsEventId => [
                'id'        => $modifyTagsEventId,
                'type'      => 'lead.changetags',
                'eventType' => 'action',
            ],
            $changeOwnerEventId => [
                'id'        => $changeOwnerEventId,
                'type'      => 'lead.changeowner',
                'eventType' => 'action',
            ],
        ];

        $uri = '/s/campaigns/events/edit/'.$modifyTagsEventId.'?campaignId='.self::TEMP_CAMPAIGN_ID;
        $this->client->xmlHttpRequest('GET', $uri, ['modifiedEvents' => json_encode($modifiedEvents)]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertSame($modifyTagsEventId, $responseData['eventId'], 'Should be able to edit the first event');
        Assert::assertArrayHasKey('newContent', $responseData, 'Edit form content should be returned');
    }
}
