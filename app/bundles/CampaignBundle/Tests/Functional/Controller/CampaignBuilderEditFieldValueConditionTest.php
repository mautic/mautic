<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class CampaignBuilderEditFieldValueConditionTest extends MauticMysqlTestCase
{
    use CampaignControllerTrait;
    use CreateTestEntitiesTrait;

    public function testCampaignBuilderFormForFieldValueConditionForInOperator(): void
    {
        $campaign = $this->setupCampaignWithLeadList();
        $version  = $campaign->getVersion();

        $campaignCondition = $this->setupCampaignEvent($campaign);

        $campaignAction = new Event();
        $campaignAction->setCampaign($campaign);
        $campaignAction->setParent($campaignCondition);
        $campaignAction->setName('Send Email 1');
        $campaignAction->setType('email.send');
        $campaignAction->setEventType('action');
        $campaignAction->setProperties([]);
        $this->em->persist($campaignAction);

        $this->em->flush();
        $this->em->clear();

        $conditionArray = $campaignCondition->convertToArray();
        unset($conditionArray['campaign'], $conditionArray['children'], $conditionArray['log'], $conditionArray['changes']);

        $campaignArray = $campaignAction->convertToArray();
        unset($campaignArray['campaign'], $campaignArray['children'], $campaignArray['log'], $campaignArray['changes'], $campaignArray['parent']);

        $modifiedEvents = [
            $campaignCondition->getId() => $conditionArray,
            $campaignAction->getId()    => $campaignArray,
        ];

        $payload = [
            'modifiedEvents' => json_encode($modifiedEvents),
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/s/campaigns/events/edit/%s', $campaignCondition->getId()), $payload, [], $this->createAjaxHeaders());
        Assert::assertTrue($this->client->getResponse()->isOk());

        // version should be incremented as campaign's "modified by user" is updated
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    public function testSwitchScalarValueToAnArrayOne(): void
    {
        $campaign = $this->setupCampaignWithLeadList();

        $campaignCondition = $this->setupCampaignEvent($campaign);
        // Start with a scalar value for the 'value' property
        $campaignCondition->setProperties([
            'field'    => 'country',
            'operator' => '=',
            'value'    => 'Afghanistan',  // scalar value
        ]);

        $this->em->flush();
        $this->em->clear();

        // Convert the event to array format and change from scalar to array value
        $conditionArray = $campaignCondition->convertToArray();
        unset($conditionArray['campaign'], $conditionArray['children'], $conditionArray['log'], $conditionArray['changes']);

        // Change the operator to 'in' and value to array (this is the core test scenario)
        $conditionArray['properties']['operator'] = 'in';
        $conditionArray['properties']['value']    = ['Albania'];  // array value

        $modifiedEvents = [
            $campaignCondition->getId() => $conditionArray,
        ];

        $payload = [
            'modifiedEvents' => json_encode($modifiedEvents),
        ];

        // The main test: ensure the EventController can handle scalar to array conversion without HTTP 500
        $this->client->request(Request::METHOD_POST, sprintf('/s/campaigns/events/edit/%s', $campaignCondition->getId()), $payload, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();

        // This is the core assertion - the request should succeed (no HTTP 500)
        Assert::assertTrue($response->isOk(), 'EventController should handle scalar to array value conversion without HTTP 500');

        // Additional verification: ensure response is valid JSON
        Assert::assertJson($response->getContent());
    }

    private function setupCampaignWithLeadList(): Campaign
    {
        $leadList = new LeadList();
        $leadList->setName('Test list');
        $leadList->setPublicName('Test list');
        $leadList->setAlias('test-list');
        $this->em->persist($leadList);

        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $campaign->addList($leadList);
        $this->em->persist($campaign);

        $lead = new Lead();
        $lead->setFirstname('Test Lead');
        $this->em->persist($lead);

        return $campaign;
    }

    private function setupCampaignEvent(Campaign $campaign): Event
    {
        $campaignCondition = new Event();
        $campaignCondition->setCampaign($campaign);
        $campaignCondition->setName('Check for country');
        $campaignCondition->setType('lead.field_value');
        $campaignCondition->setEventType('condition');
        $campaignCondition->setProperties([
            'field'    => 'country',
            'operator' => 'in',
            'value'    => ['Afghanistan'],
        ]);
        $this->em->persist($campaignCondition);

        return $campaignCondition;
    }
}
