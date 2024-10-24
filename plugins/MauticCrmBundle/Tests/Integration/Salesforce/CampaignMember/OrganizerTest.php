<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Salesforce\CampaignMember;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember\Organizer;

class OrganizerTest extends \PHPUnit\Framework\TestCase
{
    public function testRecordsAreOrganizedIntoLeadsAndContacts(): void
    {
        $records = [
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQe2AAG',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => null,
                'LeadId'     => '00Qf100000YjYv4EAF',
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQe7AAG',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => null,
                'LeadId'     => '00Qf100000YjYv9EAF',
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQeCAAW',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => null,
                'LeadId'     => '00Qf100000YjYvEEAV',
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQeHAAW',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => null,
                'LeadId'     => '00Qf100000YjYvJEAV',
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQeMAAW',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => null,
                'LeadId'     => '00Qf100000YjYvOEAV',
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQeRAAW',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => '00Qf100000YjYvTEAV',
                'LeadId'     => null,
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQeWAAW',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => '00Qf100000X1NR5EAN',
                'LeadId'     => null,
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQebAAG',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => '00Qf100000YjYvYEAV',
                'LeadId'     => null,
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQegAAG',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => '00Qf100000YjYvdEAF',
                'LeadId'     => null,
                'IsDeleted'  => false,
            ],
            [
                'attributes' => [
                    'type' => 'CampaignMember',
                    'url'  => '/services/data/v34.0/sobjects/CampaignMember/00vf100000gFQelAAG',
                ],
                'CampaignId' => '701f10000021UnkAAE',
                'ContactId'  => '00Qf100000YjYviEAF',
                'LeadId'     => null,
                'IsDeleted'  => false,
            ],
        ];

        $organizer = new Organizer($records);

        $leads     = ['00Qf100000YjYv4EAF', '00Qf100000YjYv9EAF', '00Qf100000YjYvEEAV', '00Qf100000YjYvJEAV', '00Qf100000YjYvOEAV'];
        $this->assertEquals($leads, $organizer->getLeadIds());

        $organizedLeads = $organizer->getLeads();
        foreach ($leads as $id) {
            $this->assertArrayHasKey($id, $organizedLeads);
            $this->assertEquals($id, $organizedLeads[$id]->getId());
        }

        $contacts  = ['00Qf100000YjYvTEAV', '00Qf100000X1NR5EAN', '00Qf100000YjYvYEAV', '00Qf100000YjYvdEAF', '00Qf100000YjYviEAF'];
        $this->assertEquals($contacts, $organizer->getContactIds());

        $organizedContacts = $organizer->getContacts();
        foreach ($contacts as $id) {
            $this->assertArrayHasKey($id, $organizedContacts);
            $this->assertEquals($id, $organizedContacts[$id]->getId());
        }
    }
}
