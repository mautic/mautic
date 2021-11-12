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

final class CampaignEditFieldValueConditionTest extends MauticMysqlTestCase
{
    use CampaignControllerTrait;

    public function testCampaignBuilderFormForFieldValueConditionForInOperator(): void
    {
        $campaign = $this->setupCampaignWithCondition();
        $version  = $campaign->getVersion();

        $conditionEvent = new Event();
        $conditionEvent->setCampaign($campaign);
        $conditionEvent->setName('Send Email 1');
        $conditionEvent->setType('lead.field_value');
        $conditionEvent->setEventType('condition');
        $conditionEvent->setProperties([
            'field'     => 'country',
            'operator'  => 'in',
            'value'     => 'Afghanistan',
        ]);
        $this->em->persist($conditionEvent);

        $campaignEvent = new Event();
        $campaignEvent->setCampaign($campaign);
        $campaignEvent->setParent($conditionEvent);
        $campaignEvent->setName('Send Email 1');
        $campaignEvent->setType('email.send');
        $campaignEvent->setEventType('action');
        $campaignEvent->setProperties([]);
        $this->em->persist($campaignEvent);

        $this->em->flush();
        $this->em->clear();

        $conditionArray = $conditionEvent->convertToArray();
        unset($conditionArray['campaign'], $conditionArray['children'], $conditionArray['log'], $conditionArray['changes']);

        $campaignArray = $campaignEvent->convertToArray();
        unset($campaignArray['campaign'], $campaignArray['children'], $campaignArray['log'], $campaignArray['changes'], $campaignArray['parent']);
        $modifiedEvents = [
            $conditionEvent->getId()    => $conditionArray,
            $campaignEvent->getId()     => $campaignArray,
        ];

        $payload                                    = [
            'modifiedEvents' => json_encode($modifiedEvents),
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/s/campaigns/events/edit/%s', $conditionEvent->getId()), $payload, [], $this->createAjaxHeaders());
        Assert::assertTrue($this->client->getResponse()->isOk());

        // version should be incremented as campaign's "modified by user" is updated
        $this->refreshAndSubmitForm($campaign, ++$version);
    }

    private function setupCampaignWithCondition(): Campaign
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
