<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class CampaignBuilderEditFieldValueConditionTest extends MauticMysqlTestCase
{
    use CampaignControllerTrait;

    public function testCampaignBuilderFormForFieldValueConditionForInOperator(): void
    {
        $campaign = $this->setupCampaignWithLeadList();
        $version  = $campaign->getVersion();

        $campaignCondition = new Event();
        $campaignCondition->setCampaign($campaign);
        $campaignCondition->setName('Check for country');
        $campaignCondition->setType('lead.field_value');
        $campaignCondition->setEventType('condition');
        $campaignCondition->setProperties([
            'field'     => 'country',
            'operator'  => 'in',
            'value'     => 'Afghanistan',
        ]);
        $this->em->persist($campaignCondition);

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

        $payload                                    = [
            'modifiedEvents' => json_encode($modifiedEvents),
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/s/campaigns/events/edit/%s', $campaignCondition->getId()), $payload, [], $this->createAjaxHeaders());
        Assert::assertTrue($this->client->getResponse()->isOk());

        // version should be incremented as campaign's "modified by user" is updated
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    private function setupCampaignWithLeadList(): Campaign
    {
        $leadList = new LeadList();
        $leadList->setName('Test list');
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
}
