<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Salesforce\CampaignMember;

use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember\Fetcher;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember\Organizer;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\CampaignMember;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Contact;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;

class FetcherTest extends \PHPUnit\Framework\TestCase
{
    public function testEntitiesAreFetchedFromOrganizerResults(): void
    {
        $organizer = $this->getOrgnanizer();
        $repo      = $this->createMock(IntegrationEntityRepository::class);
        $matcher   = $this->exactly(2);

        $repo->expects($matcher)
            ->method('getIntegrationsEntityId')->willReturnCallback(function (...$parameters) use ($matcher, $organizer) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertSame(Lead::OBJECT, $parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertNull($parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame($organizer->getLeadIds(), $parameters[9]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertSame(Contact::OBJECT, $parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertNull($parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame($organizer->getContactIds(), $parameters[9]);
                }

                return [];
            });

        new Fetcher($repo, $organizer, '701f10000021UnkAAE');
    }

    public function testThatCampaignMembersAreFetched(): void
    {
        $organizer = $this->getOrgnanizer();
        $repo      = $this->createMock(IntegrationEntityRepository::class);
        $matcher   = $this->exactly(4);

        $repo->expects($matcher)
            ->method('getIntegrationsEntityId')->willReturnCallback(function (...$parameters) use ($matcher, $organizer) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertSame(Lead::OBJECT, $parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertNull($parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame($organizer->getLeadIds(), $parameters[9]);

                    return [
                        [
                            'integration_entity_id' => '00Qf100000YjYvEEAV',
                            'internal_entity_id'    => 1,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYvJEAV',
                            'internal_entity_id'    => 2,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYvOEAV',
                            'internal_entity_id'    => 3,
                        ],
                    ];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertSame(Contact::OBJECT, $parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertNull($parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame($organizer->getContactIds(), $parameters[9]);

                    return [
                        [
                            'integration_entity_id' => '00Qf100000YjYvYEAV',
                            'internal_entity_id'    => 4,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYvdEAF',
                            'internal_entity_id'    => 5,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYviEAF',
                            'internal_entity_id'    => 6,
                        ],
                    ];
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertSame(CampaignMember::OBJECT, $parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertSame([1, 2, 3, 4, 5, 6], $parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame('701f10000021UnkAAE', $parameters[9]);

                    return [
                        [
                            'integration_entity'    => CampaignMember::OBJECT,
                            'integration_entity_id' => '701f10000021UnkAAE',
                            'internal_entity_id'    => 1,
                        ],
                        [
                            'integration_entity'    => CampaignMember::OBJECT,
                            'integration_entity_id' => '701f10000021UnkAAE',
                            'internal_entity_id'    => 4,
                        ],
                    ];
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Salesforce', $parameters[0]);
                    $this->assertNull($parameters[1]);
                    $this->assertSame('lead', $parameters[2]);
                    $this->assertNull($parameters[3]);
                    $this->assertNull($parameters[4]);
                    $this->assertNull($parameters[5]);
                    $this->assertFalse($parameters[6]);
                    $this->assertSame(0, $parameters[7]);
                    $this->assertSame(0, $parameters[8]);
                    $this->assertSame(['00Qf100000YjYv4EAF', '00Qf100000YjYv9EAF', '00Qf100000YjYvTEAV', '00Qf100000X1NR5EAN'], $parameters[9]);

                    return [
                        [
                            'integration_entity_id' => '00Qf100000YjYv4EAF',
                            'internal_entity_id'    => 7,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYv9EAF',
                            'internal_entity_id'    => 8,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000YjYvTEAV',
                            'internal_entity_id'    => 9,
                        ],
                        [
                            'integration_entity_id' => '00Qf100000X1NR5EAN',
                            'internal_entity_id'    => 10,
                        ],
                    ];
                }
            });

        $fetcher = new Fetcher($repo, $organizer, '701f10000021UnkAAE');

        // The query to fetch unknown members should be the 2 Leads not returned by at(0)
        $this->assertEquals(
            "SELECT Test, Id from Lead where Id in ('00Qf100000YjYv4EAF','00Qf100000YjYv9EAF') and ConvertedContactId = NULL",
            $fetcher->getQueryForUnknownObjects(['Test'], Lead::OBJECT)
        );

        // The query to fetch unknown members should be the 2 Contacts not returned by at(1)
        $this->assertEquals(
            "SELECT Test, Id from Contact where Id in ('00Qf100000YjYvTEAV','00Qf100000X1NR5EAN')",
            $fetcher->getQueryForUnknownObjects(['Test'], Contact::OBJECT)
        );

        // Should include all but the two we are already tracking as campaign members
        $unknown = $fetcher->getUnknownCampaignMembers();

        $this->assertEquals(
            [2, 3, 5, 6, 7, 8, 9, 10],
            $unknown
        );
    }

    /**
     * @return Organizer
     */
    private function getOrgnanizer()
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

        return new Organizer($records);
    }
}
