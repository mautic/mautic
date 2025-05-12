<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\EventListener\CampaignSubscriber;
use PHPUnit\Framework\Assert;

class CampaignSubscriberTest extends MauticMysqlTestCase
{
    private CampaignSubscriber $campaignSubscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignSubscriber = $this->getContainer()->get(CampaignSubscriber::class);
    }

    public function testOnCampaignTriggerConditionReturnsCorrectResultForLeadDeviceContext(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);

        $now         = new \DateTime();
        $leadDevice1 = new LeadDevice();
        $leadDevice1->setLead($lead);
        $leadDevice1->setDateAdded($now);
        $leadDevice1->setDevice('desktop');
        $leadDevice1->setDeviceBrand('AP');
        $leadDevice1->setDeviceModel('MacBook');
        $leadDevice1->setDeviceOsName('Mac');
        $this->em->persist($leadDevice1);

        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        $entityEvent = new Event();
        $entityEvent->setCampaign($campaign);
        $entityEvent->setName('Test Condition');
        $entityEvent->setEventType('condition');
        $entityEvent->setType('lead.device');
        $entityEvent->setProperties([
            'device_type' => [
                'desktop',
                'mobile',
                'tablet',
            ],
            'device_brand' => [
                'AP',
                'NOKIA',
                'SAMSUNG',
            ],
            'device_os' => [
                'Chrome OS',
                'Mac',
                'iOS',
            ],
        ]);

        $this->em->persist($entityEvent);
        $this->em->flush();

        $eventProperties = [
            'lead'            => $lead,
            'event'           => $entityEvent,
            'eventDetails'    => [],
            'systemTriggered' => false,
            'eventSettings'   => [],
        ];

        $campaignExecutionEvent = new CampaignExecutionEvent($eventProperties, false); // @phpstan-ignore new.deprecated
        $result                 = $this->campaignSubscriber->onCampaignTriggerCondition($campaignExecutionEvent);
        Assert::assertInstanceOf(CampaignExecutionEvent::class, $result); // @phpstan-ignore classConstant.deprecatedClass
        Assert::assertTrue($result->getResult());
    }
}
