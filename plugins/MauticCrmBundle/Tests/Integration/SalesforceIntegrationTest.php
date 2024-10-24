<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use PHPUnit\Framework\MockObject\MockObject;

class SalesforceIntegrationTest extends AbstractIntegrationTestCase
{
    public const SC_MULTIPLE_SF_LEADS        = 'multiple_sf_leads';

    public const SC_MULTIPLE_SF_CONTACTS     = 'multiple_sf_contacts';

    public const SC_CONVERTED_SF_LEAD        = 'converted_sf_lead';

    public const SC_EMAIL_WITH_APOSTROPHE    = 'email_with_apostrophe';

    public const SC_MULTIPLE_MAUTIC_CONTACTS = 'multiple_mautic_contacts';

    /**
     * @var array
     */
    protected $maxInvocations = [];

    /**
     * @var string|null
     */
    protected $specialSfCase;

    /**
     * @var array
     */
    protected $persistedIntegrationEntities = [];

    /**
     * @var array
     */
    protected $returnedSfEntities = [];

    /**
     * @var array
     */
    protected $mauticContacts = [
        'Contact' => [],
        'Lead'    => [],
    ];

    /**
     * @var array
     */
    protected $sfObjects = [
        'Lead',
        'Contact',
        'company',
    ];

    /**
     * @var array
     */
    protected $sfMockMethods = [
        'makeRequest',
    ];

    /**
     * @var array
     */
    protected $sfMockResetMethods = [
        'makeRequest',
    ];

    /**
     * @var array
     */
    protected $sfMockResetObjects = [
        'Lead',
        'Contact',
        'company',
    ];

    /**
     * @var int
     */
    protected $idCounter = 1;

    /**
     * @var array
     */
    protected $leadsUpdatedCounter = [
        'Lead'    => 0,
        'Contact' => 0,
    ];

    /**
     * @var int
     */
    protected $leadsCreatedCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
    }

    /**
     * Reset.
     */
    public function tearDown(): void
    {
        $this->returnedSfEntities           = [];
        $this->persistedIntegrationEntities = [];
        $this->sfMockMethods                = $this->sfMockResetMethods;
        $this->sfObjects                    = $this->sfMockResetObjects;
        $this->specialSfCase                = null;
        $this->idCounter                    = 1;
        $this->leadsCreatedCounter          = 0;
        $this->leadsUpdatedCounter          = [
            'Lead'    => 0,
            'Contact' => 0,
        ];
        $this->mauticContacts = [];
    }

    public function testPushLeadsUpdateAndCreateCorrectNumbers(): void
    {
        $sf    = $this->getSalesforceIntegration();
        $stats = $sf->pushLeads();

        $this->assertCount(400, $this->getPersistedIntegrationEntities());
        $this->assertEquals(300, $stats[0], var_export($stats, true)); // update
        $this->assertEquals(100, $stats[1], var_export($stats, true)); // create
    }

    public function testThatMultipleSfLeadsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated(): void
    {
        $this->companyModel->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([]);
        $this->specialSfCase = self::SC_MULTIPLE_SF_LEADS;
        $sf                  = $this->getSalesforceIntegration(2, 0, 2, 0, 'Lead');
        $sf->pushLeads();

        // Validate there are only two integration entities (two contacts with same email)
        $integrationEntities = $this->getPersistedIntegrationEntities();
        $this->assertCount(2, $integrationEntities);

        // Validate that there were 4 found entries (two duplicate leads)
        $sfEntities = $this->getReturnedSfEntities();
        $this->assertCount(4, $sfEntities);
    }

    public function testThatMultipleSfContactsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated(): void
    {
        $this->companyModel->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([]);
        $this->specialSfCase = self::SC_MULTIPLE_SF_CONTACTS;
        $sf                  = $this->getSalesforceIntegration(2, 0, 0, 2, 'Contact');
        $sf->pushLeads();

        // Validate there are only two integration entities (two contacts with same email)
        $integrationEntities = $this->getPersistedIntegrationEntities();
        $this->assertEquals(2, count($integrationEntities));

        // Validate that there were 4 found entries (two duplciate leads)
        $sfEntities = $this->getReturnedSfEntities();
        $this->assertEquals(4, count($sfEntities));
    }

    public function testThatLeadsAreOnlyCreatedIfEnabled(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest', 'getMauticContactsToCreate'];

        $sf = $this->getSalesforceIntegration();

        $sf->expects($this->never())
            ->method('getMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testThatLeadsAreOnlyCreatedIfLimitIsAppropriate(): void
    {
        $this->sfMockMethods = ['makeRequest', 'getMauticContactsToCreate', 'getMauticContactsToUpdate', 'getSalesforceSyncLimit'];

        $sf      = $this->getSalesforceIntegration();
        $counter = 0;
        $sf->expects($this->exactly(2))
            ->method('getMauticContactsToUpdate')
            ->will(
                $this->returnCallback(
                    function () use (&$counter) {
                        ++$counter;

                        return true;
                    }
                )
            );

        $sf->method('getSalesforceSyncLimit')
            ->willReturn(50);

        $sf->expects($this->exactly(1))
            ->method('getMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testThatLeadsAreNotCreatedIfCountIsLessThanLimit(): void
    {
        $this->sfMockMethods = ['makeRequest', 'getMauticContactsToCreate', 'getMauticContactsToUpdate', 'getSalesforceSyncLimit'];

        $sf      = $this->getSalesforceIntegration();
        $counter = 0;
        $sf->expects($this->exactly(2))
            ->method('getMauticContactsToUpdate')
            ->will(
                $this->returnCallback(
                    function () use (&$counter) {
                        ++$counter;

                        return true;
                    }
                )
            );

        $counter = 0;
        $sf->method('getSalesforceSyncLimit')
            ->will(
                $this->returnCallback(
                    function () use (&$counter) {
                        ++$counter;

                        return (1 === $counter) ? 100 : 0;
                    }
                )
            );

        $sf->expects($this->never())
            ->method('getMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testLastSyncDate(): void
    {
        $class          = new \ReflectionClass(SalesforceIntegration::class);
        $lastSyncMethod = $class->getMethod('getLastSyncDate');
        $lastSyncMethod->setAccessible(true);

        $sf = $this->getSalesforceIntegration();

        // should be a DateTime
        $lastSync = $lastSyncMethod->invokeArgs($sf, []);
        $this->assertTrue($lastSync instanceof \DateTime);

        // Set the override set by fetch command
        define('MAUTIC_DATE_MODIFIED_OVERRIDE', time());

        /** @var \DateTime $lastSync */
        $lastSync = $lastSyncMethod->invokeArgs($sf, []);

        // should be teh same as MAUTIC_DATE_MODIFIED_OVERRIDE override
        $this->assertTrue($lastSync instanceof \DateTime);
        $this->assertEquals(MAUTIC_DATE_MODIFIED_OVERRIDE, $lastSync->format('U'));

        $lead          = new Lead();
        $reflectedLead = new \ReflectionObject($lead);
        $reflectedId   = $reflectedLead->getProperty('id');
        $reflectedId->setAccessible(true);
        $reflectedId->setValue($lead, 1);

        $modified = new \DateTime('-15 minutes');
        $lead->setDateModified($modified);
        // Set it twice to get an original and updated datetime
        $now = new \DateTime();
        $lead->setDateModified($now);

        $params = [
            'start' => $modified->format('c'),
        ];

        // Should be null due to the contact was updated since last sync
        $lastSync = $lastSyncMethod->invokeArgs($sf, [$lead, $params, false]);
        $this->assertNull($lastSync);

        // Should be a DateTime object
        $lead     = new Lead();
        $lastSync = $lastSyncMethod->invokeArgs($sf, [$lead, $params]);
        $this->assertTrue($lastSync instanceof \DateTime);
    }

    public function testLeadsAreNotCreatedInSfIfFoundToAlreadyExistAsContacts(): void
    {
        $this->sfObjects     = ['Lead', 'Contact'];
        $this->sfMockMethods = ['makeRequest', 'getSalesforceObjectsByEmails', 'prepareMauticContactsToCreate'];
        $sf                  = $this->getSalesforceIntegration(0, 2);

        /*
         * This forces the integration to think the contact exists in SF,
         * and removes those emails from the array for creation.
         */
        $sf->method('getSalesforceObjectsByEmails')
            ->willReturnCallback(
                function () {
                    $args   = func_get_args();
                    $emails = array_column($args[1], 'email');

                    return $this->getSalesforceObjects($emails, 0, 1);
                }
            );

        $sf->expects($this->never())
            ->method('prepareMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testLeadsAreNotCreatedInSfIfFoundToAlreadyExistAsLeads(): void
    {
        $this->sfObjects     = ['Lead'];
        $this->sfMockMethods = ['makeRequest', 'getSalesforceObjectsByEmails', 'prepareMauticContactsToCreate'];
        $sf                  = $this->getSalesforceIntegration(0, 1);

        /*
         * This forces the integration to think the contact exists in SF,
         * and removes those emails from the array for creation.
         */
        $sf->method('getSalesforceObjectsByEmails')
            ->willReturnCallback(
                function () {
                    $args   = func_get_args();
                    $emails = array_column($args[1], 'email');

                    return $this->getSalesforceObjects($emails, 0, 1);
                }
            );

        $sf->expects($this->never())
            ->method('prepareMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testExceptionIsThrownIfSfReturnsErrorOnEmailLookup(): void
    {
        $this->sfObjects     = ['Lead'];
        $this->sfMockMethods = ['makeRequest', 'getSalesforceObjectsByEmails'];
        $sf                  = $this->getSalesforceIntegration();

        $sf->method('getSalesforceObjectsByEmails')
            ->willReturn('Some Error');

        $this->expectException(ApiErrorException::class);

        $sf->pushLeads();
    }

    public function testGetCampaigns(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest'];
        $sf                  = $this->getSalesforceIntegration();

        $sf->expects($this->once())
            ->method('makeRequest')
            ->with(
                'https://sftest.com/services/data/v34.0/query',
                [
                    'q' => 'Select Id, Name from Campaign where isDeleted = false',
                ]
            );

        $sf->getCampaigns();
    }

    public function testGetCampaignMembers(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest'];
        $sf                  = $this->getSalesforceIntegration();

        $sf->expects($this->once())
            ->method('makeRequest')
            ->with(
                'https://sftest.com/services/data/v34.0/query',
                [
                    'q' => "Select CampaignId, ContactId, LeadId, isDeleted from CampaignMember where CampaignId = '1'",
                ]
            );

        $sf->getCampaignMembers(1);
    }

    public function testGetCampaignMemberStatus(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest'];
        $sf                  = $this->getSalesforceIntegration();

        $sf->expects($this->once())
            ->method('makeRequest')
            ->with(
                'https://sftest.com/services/data/v34.0/query',
                [
                    'q' => "Select Id, Label from CampaignMemberStatus where isDeleted = false and CampaignId='1'",
                ]
            );

        $sf->getCampaignMemberStatus(1);
    }

    public function testPushToCampaign(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest'];
        $sf                  = $this->getSalesforceIntegration();

        $lead = new Lead();

        $lead->setFirstname('Lead1');
        $lead->setEmail('Lead1@sftest.com');
        $lead->setId(1);

        $sf->expects($this->any())
            ->method('makeRequest')
            ->willReturnCallback(
                function () {
                    $args = func_get_args();

                    // Checking for campaign members should return empty array for testing purposes
                    if (str_contains($args[0], '/query') && str_contains($args[1]['q'], 'CampaignMember')) {
                        return [];
                    }

                    if (str_contains($args[0], '/composite')) {
                        $this->assertSame(
                            '1-CampaignMemberNew-null-1',
                            $args[1]['compositeRequest'][0]['referenceId'],
                            'The composite request when pushing a campaign member should contain the correct referenceId.'
                        );

                        return true;
                    }
                }
            );

        $sf->pushLeadToCampaign($lead, 1, 'Active', ['Lead' => [1]]);
    }

    public function testPushCompany(): void
    {
        $this->sfObjects     = ['Account'];
        $this->sfMockMethods = ['makeRequest'];
        $sf                  = $this->getSalesforceIntegration();

        $company = new Company();

        $company->setName('MyCompanyName');

        $sf->expects($this->any())
            ->method('makeRequest')
            ->willReturnCallback(
                function () {
                    $args = func_get_args();

                    // Checking for campaign members should return empty array for testing purposes
                    if (str_contains($args[0], '/query') && str_contains($args[1]['q'], 'Account')) {
                        return [];
                    }

                    if (str_contains($args[0], '/composite')) {
                        $this->assertSame(
                            '1-Account-null-1',
                            $args[1]['compositeRequest'][0]['referenceId'],
                            'The composite request when pushing an account should contain the correct referenceId.'
                        );

                        return true;
                    }
                }
            );

        $this->assertFalse($sf->pushCompany($company));
    }

    public function testExportingContactActivity(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest', 'getSalesforceObjectsByEmails', 'isAuthorized', 'getFetchQuery', 'getLeadData'];
        $sf                  = $this->getSalesforceIntegration(2, 2);

        $sf->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $sf->expects($this->once())
            ->method('getFetchQuery')
            ->with([])
            ->willReturnCallback(
                fn () => [
                    'start' => '-1 week',
                    'end'   => 'now',
                ]
            );

        $this->setMaxInvocations('getIntegrationsEntityId', 1);

        $sf->expects($this->once())
            ->method('getLeadData')
            ->willReturnCallback(
                function () {
                    $leadIds = func_get_arg(2);
                    $data    = [];

                    foreach ($leadIds as $i => $id) {
                        ++$i;

                        $data[$id] = [
                            'records' => [
                                [
                                    'eventType'   => 'email',
                                    'name'        => 'Email Name',
                                    'description' => 'Email sent',
                                    'dateAdded'   => new \DateTime(),
                                    'id'          => 'pointChange'.$i,
                                ],
                            ],
                        ];
                    }

                    return $data;
                }
            );

        /*
         * Ensures that makeRequest is called with the mautic_timeline__c endpoint.
         * If it is, then we've successfully exported contact activity.
         */
        $sf->expects($this->exactly(1))
            ->method('makeRequest')
            ->with('https://sftest.com/services/data/v38.0/composite/')
            ->willReturnCallback(
                fn () => $this->getSalesforceCompositeResponse(func_get_arg(1))
            );

        $sf->pushLeadActivity();
    }

    public function testMauticContactTimelineLinkPopulatedsPayload(): void
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest', 'getSalesforceObjectsByEmails'];
        $sf                  = $this->getSalesforceIntegration(2, 2);

        /*
         * When checking if contacts need to be created, the mauticContactTimelineLink
         * has been populated at this point. If populated here, the test passes.
         * We return the salesforce objects so as not to throw an error.
         */
        $sf->method('getSalesforceObjectsByEmails')
            ->willReturnCallback(
                function () {
                    $args   = func_get_args();
                    $emails = array_column($args[1], 'email');

                    return $this->getSalesforceObjects($emails, 0, 1);
                }
            );

        /*
         * With the given SF integration setup, the `getSalesforceObjectsByEmails` method
         * will be called once, and have the given parameters, which contains the contact
         * timeline link.
         */
        $sf->expects($this->once())
            ->method('getSalesforceObjectsByEmails')
            ->with(
                'Contact',
                [
                    'contact1@sftest.com' => [
                        'integration_entity_id'             => 'SF1',
                        'integration_entity'                => 'Contact',
                        'id'                                => 1,
                        'internal_entity_id'                => 1,
                        'firstname'                         => 'Contact1',
                        'lastname'                          => 'Contact1',
                        'company'                           => 'Contact1',
                        'email'                             => 'Contact1@sftest.com',
                        'mauticContactTimelineLink'         => 'mautic_plugin_timeline_view',
                        'mauticContactIsContactableByEmail' => 1,
                        'mauticContactId'                   => 1,
                    ],
                    'contact2@sftest.com' => [
                        'integration_entity_id'             => 'SF2',
                        'integration_entity'                => 'Contact',
                        'id'                                => 2,
                        'internal_entity_id'                => 2,
                        'firstname'                         => 'Contact2',
                        'lastname'                          => 'Contact2',
                        'company'                           => 'Contact2',
                        'email'                             => 'Contact2@sftest.com',
                        'mauticContactTimelineLink'         => 'mautic_plugin_timeline_view',
                        'mauticContactIsContactableByEmail' => 1,
                        'mauticContactId'                   => 2,
                    ],
                ],
                'FirstName,LastName,Email'
            );

        $sf->pushLeads();
    }

    public function testUpdateDncBySfDate(): void
    {
        $this->sfMockMethods = ['makeRequest', 'updateDncByDate', 'getDncHistory'];

        $objects = ['Contact', 'Lead'];
        foreach ($objects as $object) {
            $mappedData = [
                'contact1@sftest.com' => [
                    'integration_entity_id'             => 'SF1',
                    'integration_entity'                => $object,
                    'id'                                => 1,
                    'internal_entity_id'                => 1,
                    'firstname'                         => 'Contact1',
                    'lastname'                          => 'Contact1',
                    'company'                           => 'Contact1',
                    'email'                             => 'Contact1@sftest.com',
                    'mauticContactTimelineLink'         => 'mautic_plugin_timeline_view',
                    'mauticContactIsContactableByEmail' => 1,
                ],
                'contact2@sftest.com' => [
                    'integration_entity_id'             => 'SF2',
                    'integration_entity'                => $object,
                    'id'                                => 2,
                    'internal_entity_id'                => 2,
                    'firstname'                         => 'Contact2',
                    'lastname'                          => 'Contact2',
                    'company'                           => 'Contact2',
                    'email'                             => 'Contact2@sftest.com',
                    'mauticContactTimelineLink'         => 'mautic_plugin_timeline_view',
                    'mauticContactIsContactableByEmail' => 0,
                ],
            ];

            $sf = $this->getSalesforceIntegration(2, 2);
            $sf->expects($this->any())->method('updateDncByDate')->willReturn(true);
            $sf->expects($this->any())
                ->method('getDncHistory')
                ->willReturn(
                    $this->getSalesforceDNCHistory($object, 'SF')
                );
            $sf->pushLeadDoNotContactByDate('email', $mappedData, $object, ['start' => '2017-10-16 13:00:00.000000']);

            foreach ($mappedData as $assertion) {
                $this->assertArrayHasKey('mauticContactIsContactableByEmail', $assertion);
            }
        }
    }

    public function testUpdateDncByMauticDate(): void
    {
        $this->sfMockMethods = ['makeRequest', 'updateDncByDate', 'getDoNotContactHistory'];

        $objects = ['Contact', 'Lead'];
        foreach ($objects as $object) {
            $mappedData = [
                'contact1@sftest.com' => [
                    'integration_entity_id'             => 'SF1',
                    'integration_entity'                => $object,
                    'id'                                => 1,
                    'internal_entity_id'                => 1,
                    'firstname'                         => 'Contact1',
                    'lastname'                          => 'Contact1',
                    'company'                           => 'Contact1',
                    'email'                             => 'Contact1@sftest.com',
                    'mauticContactTimelineLink'         => 'mautic_plugin_timeline_view',
                    'mauticContactIsContactableByEmail' => 1,
                ],
            ];

            $sf = $this->getSalesforceIntegration(2, 2);
            $sf->expects($this->any())->method('updateDncByDate')->willReturn(true);
            $sf->expects($this->any())->method('getDoNotContactHistory')->willReturn($this->getSalesforceDNCHistory($object, 'Mautic'));

            $sf->pushLeadDoNotContactByDate('email', $mappedData, $object, ['start' => '2017-10-15T10:00:00.000000']);
            foreach ($mappedData as $assertion) {
                $this->assertArrayNotHasKey('mauticContactIsContactableByEmail', $assertion);
            }
        }
    }

    public function testAmendLeadDataBeforePush(): void
    {
        $input = ['first', false, 'first|second', 1];

        $output = ['first', false, 'first;second', 1];

        $sf = $this->getSalesforceIntegration();
        $sf->amendLeadDataBeforePush($input);

        self::assertSame($input, $output);
        self::assertEquals('string', gettype($output[0]));
        self::assertEquals('boolean', gettype($output[1]));
        self::assertEquals('string', gettype($output[2]));
        self::assertEquals('integer', gettype($output[3]));
    }

    /**
     * @param string $name
     * @param int    $max
     *
     * @return $this
     */
    protected function setMaxInvocations($name, $max)
    {
        $this->maxInvocations[$name] = $max;

        return $this;
    }

    /**
     * @return int
     */
    protected function getMaxInvocations($name)
    {
        return $this->maxInvocations[$name] ?? 1;
    }

    protected function setMocks()
    {
        $integrationEntityRepository = $this->getMockBuilder(IntegrationEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need insight into the entities persisted
        $integrationEntityRepository->method('saveEntities')
            ->willReturnCallback(
                function (): void {
                    $this->persistedIntegrationEntities = array_merge($this->persistedIntegrationEntities, func_get_arg(0));
                }
            );

        $integrationEntityRepository
            ->expects($spy = $this->any())
            ->method('getIntegrationsEntityId')
            ->willReturnCallback(
                function () use ($spy) {
                    // WARNING: this is using a PHPUnit undocumented workaround:
                    // https://github.com/sebastianbergmann/phpunit/issues/3888
                    $spyParentProperties = self::getParentPrivateProperties($spy);
                    $invocations         = $spyParentProperties['invocations'];

                    if (count($invocations) > $this->getMaxInvocations('getIntegrationsEntityId')) {
                        return [];
                    }

                    // Just return some bogus entities for testing
                    return $this->getLeadsToUpdate('Lead', 2, 2, 'Lead')['Lead'];
                }
            );
        $auditLogRepo = $this->getMockBuilder(AuditLogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $auditLogRepo
            ->expects($this->any())
            ->method('getAuditLogsForLeads')
            ->willReturn(
                [
                    [
                        'userName' => 'Salesforce',
                        'userId'   => 0,
                        'bundle'   => 'lead',
                        'object'   => 'lead',
                        'objectId' => 1,
                        'action'   => 'update',
                        'details'  => [
                            'dnc_channel_status' => [
                                'email' => [
                                    'reason'   => 3,
                                    'comments' => 'Set by Salesforce',
                                ],
                            ],
                            'dnc_status' => [
                                'manual',
                                'Set by Salesforce',
                            ],
                        ],
                        'dateAdded' => new \DateTime('2017-10-16 15:00:36.000000', new \DateTimeZone('UTC')),
                        'ipAddress' => '127.0.0.1',
                    ],
                ]
            );

        $this->em->method('getRepository')
            ->willReturnMap(
                [
                    [IntegrationEntity::class, $integrationEntityRepository],
                    [\Mautic\CoreBundle\Entity\AuditLog::class, $auditLogRepo],
                ]
            );

        $this->em->method('getReference')
            ->willReturnCallback(
                function () {
                    switch (func_get_arg(0)) {
                        case IntegrationEntity::class:
                            return new IntegrationEntity();
                    }
                }
            );

        $this->router->method('generate')
            ->willReturnArgument(0);

        $this->leadModel->method('getEntity')
            ->willReturn(new Lead());

        $this->companyModel->method('getEntity')
            ->willReturn(new Company());
        $this->companyModel->method('getEntities')
            ->willReturn([]);

        $leadFields = [
            'Id__Lead' => [
                'type'        => 'string',
                'label'       => 'Lead-Lead ID',
                'required'    => false,
                'group'       => 'Lead',
                'optionLabel' => 'Lead ID',
            ],
            'LastName__Lead' => [
                'type'        => 'string',
                'label'       => 'Lead-Last Name',
                'required'    => true,
                'group'       => 'Lead',
                'optionLabel' => 'Last Name',
            ],
            'FirstName__Lead' => [
                'type'        => 'string',
                'label'       => 'Lead-First Name',
                'required'    => false,
                'group'       => 'Lead',
                'optionLabel' => 'First Name',
            ],
            'Company__Lead' => [
                'type'        => 'string',
                'label'       => 'Lead-Company',
                'required'    => true,
                'group'       => 'Lead',
                'optionLabel' => 'Company',
            ],
            'Email__Lead' => [
                'type'        => 'string',
                'label'       => 'Lead-Email',
                'required'    => false,
                'group'       => 'Lead',
                'optionLabel' => 'Email',
            ],
        ];
        $contactFields = [
            'Id__Contact' => [
                'type'        => 'string',
                'label'       => 'Contact-Contact ID',
                'required'    => false,
                'group'       => 'Contact',
                'optionLabel' => 'Contact ID',
            ],
            'LastName__Contact' => [
                'type'        => 'string',
                'label'       => 'Contact-Last Name',
                'required'    => true,
                'group'       => 'Contact',
                'optionLabel' => 'Last Name',
            ],
            'FirstName__Contact' => [
                'type'        => 'string',
                'label'       => 'Contact-First Name',
                'required'    => false,
                'group'       => 'Contact',
                'optionLabel' => 'First Name',
            ],
            'Email__Contact' => [
                'type'        => 'string',
                'label'       => 'Contact-Email',
                'required'    => false,
                'group'       => 'Contact',
                'optionLabel' => 'Email',
            ],
        ];

        $this->cache
            ->method('get')
            ->willReturnMap(
                [
                    ['leadFields.Lead', null, $leadFields],
                    ['leadFields.Contact', null, $contactFields],
                ]
            );

        $this->cache->method('getCache')
            ->willReturn($this->cache);
    }

    /**
     * @param int $maxUpdate
     * @param int $maxCreate
     * @param int $maxSfLeads
     * @param int $maxSfContacts
     *
     * @return SalesforceIntegration|MockObject
     */
    protected function getSalesforceIntegration($maxUpdate = 100, $maxCreate = 200, $maxSfLeads = 25, $maxSfContacts = 25, $updateObject = null)
    {
        $this->setMocks();

        $featureSettings = [
            'sandbox' => [
            ],
            'updateOwner' => [
            ],
            'objects'    => $this->sfObjects,
            'namespace'  => null,
            'leadFields' => [
                'Company__Lead'      => 'company',
                'FirstName__Lead'    => 'firstname',
                'LastName__Lead'     => 'lastname',
                'Email__Lead'        => 'email',
                'FirstName__Contact' => 'firstname',
                'LastName__Contact'  => 'lastname',
                'Email__Contact'     => 'email',
            ],
            'update_mautic' => [
                'Company__Lead'      => '0',
                'FirstName__Lead'    => '0',
                'LastName__Lead'     => '0',
                'Email__Lead'        => '0',
                'FirstName__Contact' => '0',
                'LastName__Contact'  => '0',
                'Email__Contact'     => '0',
            ],
            'companyFields' => [
                'Name' => 'companyname',
            ],
            'update_mautic_company' => [
                'Name' => '0',
            ],
        ];

        $integration = new Integration();
        $integration->setIsPublished(true)
            ->setName('Salesforce')
            ->setPlugin('MauticCrmBundle')
            ->setApiKeys(
                [
                    'access_token' => '123',
                    'instance_url' => 'https://sftest.com',
                ]
            )
            ->setFeatureSettings($featureSettings)
            ->setSupportedFeatures(
                [
                    'get_leads',
                    'push_lead',
                    'push_leads',
                ]
            );

        $integrationEntityModelMock = $this->getMockBuilder(IntegrationEntityModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integrationEntityModelMock->method('getEntityByIdAndSetSyncDate')
            ->willReturn(new IntegrationEntity());

        $sf = $this->getMockBuilder(SalesforceIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->session,
                $this->request,
                $this->router,
                $this->translator,
                $this->logger,
                $this->encryptionHelper,
                $this->leadModel,
                $this->companyModel,
                $this->pathsHelper,
                $this->notificationModel,
                $this->fieldModel,
                $integrationEntityModelMock,
                $this->doNotContact,
                $this->fieldsWithUniqueIdentifier,
            ])
            ->onlyMethods($this->sfMockMethods)
            ->addMethods(['findLeadsToCreate'])
            ->getMock();

        $sf->method('makeRequest')
            ->will(
                $this->returnCallback(
                    function () use ($maxSfContacts, $maxSfLeads, $updateObject) {
                        $args = func_get_args();
                        // Determine what to return by analyzing the URL and query parameters
                        switch (true) {
                            case str_contains($args[0], '/query'):
                                if (isset($args[1]['q']) && str_contains($args[0], 'from CampaignMember')) {
                                    return [];
                                } elseif (isset($args[1]['q']) && str_contains($args[1]['q'], 'from Campaign')) {
                                    return [
                                        'totalSize' => 0,
                                        'records'   => [],
                                    ];
                                } elseif (isset($args[1]['q']) && str_contains($args[1]['q'], 'from Account')) {
                                    return [
                                        'totalSize' => 0,
                                        'records'   => [],
                                    ];
                                } elseif (isset($args[1]['q']) && 'SELECT CreatedDate from Organization' === $args[1]['q']) {
                                    return [
                                        'records' => [
                                            ['CreatedDate' => '2012-10-30T17:56:50.000+0000'],
                                        ],
                                    ];
                                } elseif (isset($args[1]['q']) && str_contains($args[1]['q'], 'from '.$updateObject.'History')) {
                                    return $this->getSalesforceDNCHistory($updateObject, 'Mautic');
                                } else {
                                    // Extract emails
                                    $found = preg_match('/Email in \(\'(.*?)\'\)/', $args[1]['q'], $match);
                                    if ($found) {
                                        $emails = explode("','", $match[1]);

                                        return $this->getSalesforceObjects($emails, $maxSfContacts, $maxSfLeads);
                                    } else {
                                        return $this->getSalesforceObjects([], $maxSfContacts, $maxSfLeads);
                                    }
                                }
                                // no break
                            case str_contains($args[0], '/composite'):
                                return $this->getSalesforceCompositeResponse($args[1]);
                        }
                    }
                )
            );

        /* @var \PHPUnit\Framework\MockObject\MockObject $this->>dispatcher */
        $this->dispatcher->method('dispatch')
            ->will(
                $this->returnCallback(
                    function () use ($sf, $integration) {
                        $args = func_get_args();

                        return match ($args[0]) {
                            default => new PluginIntegrationKeyEvent($sf, $integration->getApiKeys()),
                        };
                    }
                )
            );

        $sf->setIntegrationSettings($integration);

        $repo = $sf->getIntegrationEntityRepository();
        $this->setLeadsToUpdate($repo, $maxUpdate, $maxSfContacts, $maxSfLeads, $updateObject);
        $this->setLeadsToCreate($repo, $maxCreate);

        return $sf;
    }

    protected function setLeadsToUpdate(MockObject $mockRepository, $max, $maxSfContacts, $maxSfLeads, $specificObject)
    {
        $mockRepository->method('findLeadsToUpdate')
            ->willReturnCallback(
                function () use ($max, $specificObject) {
                    $args   = func_get_args();
                    $object = $args[6];

                    // determine whether to return a count or records
                    $results = [];
                    if (false === $args[3]) {
                        foreach ($object as $object) {
                            if ($specificObject && $specificObject !== $object) {
                                continue;
                            }

                            // Should be 100 contacts and 100 leads
                            $results[$object] = $max;
                        }

                        return $results;
                    }

                    return $this->getLeadsToUpdate($object, $args[3], $max, $specificObject);
                }
            );
    }

    /**
     * @param int $max
     */
    protected function setLeadsToCreate(MockObject $mockRepository, $max = 200)
    {
        $mockRepository->method('findLeadsToCreate')
            ->willReturnCallback(
                function () use ($max) {
                    $args = func_get_args();

                    if (false === $args[2]) {
                        return $max;
                    }

                    $createLeads = $this->getLeadsToCreate($args[2], $max);

                    // determine whether to return a count or records
                    if (false === $args[2]) {
                        return count($createLeads);
                    }

                    return $createLeads;
                }
            );
    }

    /**
     * Simulate looping over Mautic leads to update.
     *
     * @return array
     */
    protected function getLeadsToUpdate($object, $limit, $max, $specificObject)
    {
        $entities = [
            $object => [],
        ];

        // Should be 100 each
        if (($this->leadsUpdatedCounter[$object] >= $max) || ($specificObject && $specificObject !== $object)) {
            return $entities;
        }

        if ($limit > $max) {
            $limit = $max;
        }

        $counter = 0;
        while ($counter < $limit) {
            $entities[$object][$this->idCounter] = [
                'integration_entity_id' => 'SF'.$this->idCounter,
                'integration_entity'    => $object,
                'id'                    => $this->idCounter,
                'internal_entity_id'    => $this->idCounter,
                'firstname'             => $object.$this->idCounter,
                'lastname'              => $object.$this->idCounter,
                'company'               => $object.$this->idCounter,
                'email'                 => $object.$this->idCounter.'@sftest.com',
            ];

            ++$counter;
            ++$this->idCounter;
            ++$this->leadsUpdatedCounter[$object];
        }

        $this->mauticContacts = array_merge($entities[$object], $this->mauticContacts);

        return $entities;
    }

    /**
     * Simulate looping over Mautic leads to create.
     *
     * @return array
     */
    protected function getLeadsToCreate($limit, $max = 200)
    {
        $entities = [];

        if ($this->leadsCreatedCounter > $max) {
            return $entities;
        }

        if ($limit > $max) {
            $limit = $max;
        }

        $counter = 0;
        while ($counter < $limit) {
            // Start after the update
            $entities[$this->idCounter] = [
                'id'                 => $this->idCounter,
                'internal_entity_id' => $this->idCounter,
                'firstname'          => 'Lead'.$this->idCounter,
                'lastname'           => 'Lead'.$this->idCounter,
                'company'            => 'Lead'.$this->idCounter,
                'email'              => 'Lead'.$this->idCounter.'@sftest.com',
            ];

            ++$this->idCounter;
            ++$counter;
            ++$this->leadsCreatedCounter;
        }

        $this->mauticContacts = array_merge($entities, $this->mauticContacts);

        return $entities;
    }

    /**
     * Mock SF response.
     *
     * @return array
     */
    protected function getSalesforceObjects($emails, $maxContacts, $maxLeads)
    {
        // Let's find around $max records
        $records      = [];
        $contactCount = 0;
        $leadCount    = 0;

        if (!empty($emails)) {
            foreach ($emails as $email) {
                // Extact ID
                preg_match('/(Lead|Contact)([0-9]*)@sftest\.com/', $email, $match);
                $object = $match[1];

                if ('Lead' === $object) {
                    if ($leadCount >= $maxLeads) {
                        continue;
                    }
                    ++$leadCount;
                } else {
                    if ($contactCount >= $maxContacts) {
                        continue;
                    }
                    ++$contactCount;
                }

                $id        = $match[2];
                $records[] = [
                    'attributes' => [
                        'type' => $object,
                        'url'  => "/services/data/v34.0/sobjects/$object/SF$id",
                    ],
                    'Id'        => 'SF'.$id,
                    'FirstName' => $object.$id,
                    'LastName'  => $object.$id,
                    'Email'     => $object.$id.'@sftest.com',
                ];

                $this->addSpecialCases($id, $records);
            }
        }

        $this->returnedSfEntities = array_merge($this->returnedSfEntities, $records);

        return [
            'totalSize' => count($records),
            'done'      => true,
            'records'   => $records,
        ];
    }

    /**
     * Mock SF response.
     *
     * @return array
     */
    protected function getSalesforceDNCHistory($object, $priority)
    {
        $datePriority = [
            'SF'     => '2017-10-16T00:43:43.000+0000',
            'Mautic' => '2017-10-16T18:43:43.000+0000',
        ];

        return [
            'totalSize' => 3,
            'done'      => 1,
            'records'   => [
                [
                    'attributes' => [
                        'type' => 'ContactHistory',
                        'url'  => '/services/data/v34.0/sobjects/'.$object.'History/0170SFH1',
                    ],
                    'Field'       => 'HasOptedOutOfEmail',
                    $object.'Id'  => 'SF1',
                    'CreatedDate' => $datePriority[$priority],
                    'IsDeleted'   => false,
                    'NewValue'    => true,
                ],
                [
                    'attributes' => [
                        'type' => 'ContactHistory',
                        'url'  => '/services/data/v34.0/sobjects/'.$object.'History/0170SFH3',
                    ],
                    'Field'       => 'HasOptedOutOfEmail',
                    $object.'Id'  => 'SF2',
                    'CreatedDate' => $datePriority[$priority],
                    'IsDeleted'   => false,
                    'NewValue'    => true,
                ],
            ],
        ];
    }

    protected function addSpecialCases($id, &$records)
    {
        switch ($this->specialSfCase) {
            case self::SC_MULTIPLE_SF_LEADS:
                $records[] = [
                    'attributes' => [
                        'type' => 'Lead',
                        'url'  => '/services/data/v34.0/sobjects/Lead/SF'.$id.'b',
                    ],
                    'Id'                 => 'SF'.$id.'b',
                    'FirstName'          => 'Lead'.$id,
                    'LastName'           => 'Lead'.$id,
                    'Email'              => 'Lead'.$id.'@sftest.com',
                    'Company'            => 'Lead'.$id,
                    'ConvertedContactId' => null,
                ];
                break;

            case self::SC_MULTIPLE_SF_CONTACTS:
                $records[] = [
                    'attributes' => [
                        'type' => 'Contact',
                        'url'  => '/services/data/v34.0/sobjects/Contact/SF'.$id.'b',
                    ],
                    'Id'                 => 'SF'.$id.'b',
                    'FirstName'          => 'Contact'.$id,
                    'LastName'           => 'Contact'.$id,
                    'Email'              => 'Contact'.$id.'@sftest.com',
                    'Company'            => 'Contact'.$id,
                    'ConvertedContactId' => null,
                ];
                break;
        }
    }

    /**
     * Mock SF response.
     *
     * @return array
     */
    protected function getSalesforceCompositeResponse($data)
    {
        $response = [];
        foreach ($data['compositeRequest'] as $subrequest) {
            if ('PATCH' === $subrequest['method']) {
                $response[] = [
                    'body'           => null,
                    'httpHeaders'    => [],
                    'httpStatusCode' => 204,
                    'referenceId'    => $subrequest['referenceId'],
                ];
            } else {
                $contactId = '';
                $parts     = explode('-', $subrequest['referenceId']);

                if (3 === count($parts)) {
                    [$contactId, $sfObject, $id] = $parts;
                } elseif (2 === count($parts)) {
                    [$contactId, $sfObject] = $parts;
                } elseif (4 === count($parts)) {
                    [$contactId, $sfObject, $empty, $campaignId] = $parts;
                }
                $response[] = [
                    'body' => [
                        'id'      => 'SF'.$contactId,
                        'success' => true,
                        'errors'  => [],
                    ],
                    'httpHeaders' => [
                        'Location' => '/services/data/v38.0/sobjects/'.$sfObject.'/SF'.$contactId,
                    ],
                    'httpStatusCode' => 201,
                    'referenceId'    => $subrequest['referenceId'],
                ];
            }
        }

        return ['compositeResponse' => $response];
    }

    /**
     * @return array
     */
    protected function getPersistedIntegrationEntities()
    {
        $entities                           = $this->persistedIntegrationEntities;
        $this->persistedIntegrationEntities = [];

        return $entities;
    }

    protected function getReturnedSfEntities()
    {
        $entities                 = $this->returnedSfEntities;
        $this->returnedSfEntities = [];

        return $entities;
    }

    protected function getMauticContacts()
    {
        $contacts             = $this->mauticContacts;
        $this->mauticContacts = [
            'Contact' => [],
            'Lead'    => [],
        ];

        return $contacts;
    }

    /**
     * This function determines the parent of the class instance provided, and returns all properties of its parent.
     * Inspired from https://github.com/sebastianbergmann/phpunit/issues/3888#issuecomment-559513371.
     *
     * Result structure:
     *  Array =>[
     *     'parentPropertyName1' => 'value1'
     *     'parentPropertyName2' => 'value2'
     *     ...
     *  ]
     *
     * @throws \ReflectionException
     */
    private static function getParentPrivateProperties($instance): array
    {
        $reflectionClass       = new \ReflectionClass($instance::class);
        $parentReflectionClass = $reflectionClass->getParentClass();

        $parentProperties = [];

        foreach ($parentReflectionClass->getProperties() as $p) {
            $p->setAccessible(true);
            $parentProperties[$p->getName()] = $p->getValue($instance);
        }

        return $parentProperties;
    }
}
