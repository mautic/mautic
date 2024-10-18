<?php

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CampaignActionAnonymizeUserDataSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public const LEAD_DEFAULT_DEFINES = [
        'firstname' => 'Test',
        'lastname'  => 'User',
        'city'      => 'City',
        'zipcode'   => 'Zipcode',
        'address1'  => 'Address 1',
        'address2'  => 'Address 2',
        'instagram' => 'Instagram',
        'fax'       => 'Fax',
        'twitter'   => 'Twitter',
        'linkedin'  => 'LinkedIn',
        'company'   => 'Company',
    ];

    public function testRunCampaignWithAnonymizeUserDataAction(): void
    {
        $campaign     = $this->createCampaign();
        $event        = $this->createEvent($campaign);
        $preDefLead1  = 'Foo';
        $preDefLead2  = 'Bar';
        $lead1        = $this->createLead($preDefLead1);
        $lead2        = $this->createLead($preDefLead2);
        $campaignLead = [
            $this->createLeadCampaign($campaign, $lead1),
            $this->createLeadCampaign($campaign, $lead2),
        ];
        $this->em->clear();

        // Execute Campaign
        $test = $this->testSymfonyCommand(
            'mautic:campaigns:trigger',
            ['--campaign-id' => $campaign->getId()]
        );

        // Check if the leads are anonymized
        $freshLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $this->assertFalse($freshLead1->getField('position'));
        $this->assertNotSame($lead1->getAddress1(), $freshLead1->getAddress1());
        $this->assertNotSame($lead1->getAddress2(), $freshLead1->getAddress2());
        $this->assertIsArray($lead1->getField('address2'));
        $this->assertFalse($freshLead1->getField('address2'));
        $this->assertNull($freshLead1->getAddress2());
        $this->assertNotSame($lead1->getFirstname(), $freshLead1->getFirstname());
        $this->assertNotSame($lead1->getLastname(), $freshLead1->getLastname());
        $this->assertNotNull($freshLead1->getFirstname());
        $this->assertNotSame($lead1->getField('position'), $freshLead1->getField('position'));
        $this->assertNotSame($lead1->getPosition(), $freshLead1->getPosition());
        $this->assertNull($freshLead1->getPosition());
        $this->assertNotSame($lead1->getField('instagram'), $freshLead1->getField('instagram'));
        $this->assertNotSame($lead1->getEmail(), $freshLead1->getEmail());
        $this->assertStringContainsString('@ano.nym', $freshLead1->getEmail());
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign With Anonymize User Data');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        // Fields: Firstname, Lastname, Address Line 1, Instagram, Email
        $fieldsToAnonymize = ['2', '3', '11', '25', '6'];
        // Fields: Position, Address Line 2
        $fieldsToDelete = ['5', '12'];
        // Create event: Anonymize User Data
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Anonymize User Data');
        $event->setType('lead.action_anonymizeuserdata');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setProperties([
            'pseudonymize'      => '1',
            'fieldsToAnonymize' => $fieldsToAnonymize,
            'fieldsToDelete'    => $fieldsToDelete,
        ]);
        $event->setDecisionPath('yes');
        $event->setOrder(1);

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createLead(string $preDefinition): Lead
    {
        $lead = new Lead();
        $lead->setEmail($preDefinition.'test@test.com');
        $lead->setFirstname($preDefinition.' Test');
        $lead->setLastname($preDefinition.' User');
        $lead->setCity($preDefinition.' City');
        $lead->setZipcode($preDefinition.' Zipcode');
        $lead->setAddress1($preDefinition.self::LEAD_DEFAULT_DEFINES['address1']);
        $lead->setAddress2($preDefinition.' Address 2');
        $fields = [
            'position'  => $preDefinition.' Position',
            'instagram' => $preDefinition.' Instagram',
            'twitter'   => $preDefinition.' Twitter',
            'linkedin'  => $preDefinition.' LinkedIn',
            'company'   => $preDefinition.' Company',
        ];

        $this->em->getRepository(Lead::class)->saveEntity($lead);
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $leadModel->setFieldValues($lead, $fields);

        return $lead;
    }

    private function createLeadCampaign(Campaign $campaign, Lead $lead): CampaignLead
    {
        // Create Campaign Lead
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());

        $this->em->persist($campaignLead);
        $this->em->flush();

        return $campaignLead;
    }
}
