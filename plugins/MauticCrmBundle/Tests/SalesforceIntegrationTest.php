<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Router;

/**
 * Class SalesforceIntegrationTest.
 */
class SalesforceIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const SC_MULTIPLE_SF_LEADS        = 'multiple_sf_leads';
    const SC_MULTIPLE_SF_CONTACTS     = 'multiple_sf_contacts';
    const SC_CONVERTED_SF_LEAD        = 'converted_sf_lead';
    const SC_EMAIL_WITH_APOSTROPHE    = 'email_with_apostrophe';
    const SC_MULTIPLE_MAUTIC_CONTACTS = 'multiple_mautic_contacts';

    /**
     * @var
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

    /**
     * Reset.
     */
    public function tearDown()
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

    public function testPushLeadsUpdateAndCreateCorrectNumbers()
    {
        $sf    = $this->getSalesforceIntegration();
        $stats = $sf->pushLeads();

        $this->assertEquals(400, count($this->getPersistedIntegrationEntities()));
        $this->assertEquals(300, $stats[0], var_export($stats, true)); // update
        $this->assertEquals(100, $stats[1], var_export($stats, true)); // create
    }

    public function testThatMultipleSfLeadsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated()
    {
        $this->specialSfCase = self::SC_MULTIPLE_SF_LEADS;
        $sf                  = $this->getSalesforceIntegration(2, 0, 2, 0, 'Lead');
        $sf->pushLeads();

        // Validate there are only two integration entities (two contacts with same email)
        $integrationEntities = $this->getPersistedIntegrationEntities();
        $this->assertEquals(2, count($integrationEntities));

        // Validate that there were 4 found entries (two duplciate leads)
        $sfEntities = $this->getReturnedSfEntities();
        $this->assertEquals(4, count($sfEntities));
    }

    public function testThatMultipleSfContactsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated()
    {
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

    public function testThatMultipleMauticContactsAreNotDuplicatedInSF()
    {
    }

    public function testThatLeadsAreOnlyCreatedIfEnabled()
    {
        $this->sfObjects     = ['Contact'];
        $this->sfMockMethods = ['makeRequest', 'findLeadsToCreate', 'getMauticContactsToCreate'];

        $sf = $this->getSalesforceIntegration();
        $sf->expects($this->never())
            ->method('findLeadsToCreate');

        $sf->expects($this->never())
            ->method('getMauticContactsToCreate');

        $sf->pushLeads();
    }

    public function testThatLeadsAreOnlyCreatedIfLimitIsAppropriate()
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

    public function testThatLeadsAreNotCreatedIfCountIsLessThanLimit()
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

    public function testLastSyncDate()
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

        $lead     = new Lead();
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

    public function testThatMissingRequiredDataIsPulledFromSfAndHydrated()
    {
    }

    public function testLeadsAreNotCreatedInSfIfFoundToAlreadyExistAsContacts()
    {
    }

    public function testLeadsAreNotCreatedInSfIfFoundToAlreadyExistAsLeads()
    {
    }

    public function testIntegrationEntityRecordIsCreatedForFoundSfContacts()
    {
    }

    public function testNonMatchingMauticContactsAreCreated()
    {
    }

    public function testExceptionIsThrownIfSfReturnsErrorOnEmailLookup()
    {
    }

    public function testIntegrationPushFindsDuplicate()
    {
    }

    public function testIntegrationPushCreatesNew()
    {
    }

    public function testApostropheInEmailDoesNotCauseDuplicates()
    {
    }

    public function testExistingEntityRecordsDoesNotCreate()
    {
    }

    public function testMauticContactTimelineLinkPopulatedsPayload()
    {
    }

    protected function getMockFactory()
    {
        defined('IN_MAUTIC_CONSOLE') or define('IN_MAUTIC_CONSOLE', 1);

        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockIntegrationEntityRepository = $this->getMockBuilder(IntegrationEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need insight into the entities persisted
        $mockIntegrationEntityRepository->method('saveEntities')
            ->will(
                $this->returnCallback(
                    function () {
                        $args = func_get_args();
                        $this->persistedIntegrationEntities = array_merge($this->persistedIntegrationEntities, $args[0]);
                    }
                )
            );
        $mockEntityManager->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticPluginBundle:IntegrationEntity', $mockIntegrationEntityRepository],
                    ]
                )
            );

        $mockEntityManager->method('getReference')
            ->will(
                $this->returnCallback(
                    function () {
                        $args = func_get_args();
                        switch ($args[0]) {
                            case 'MauticPluginBundle:IntegrationEntity':
                                return new IntegrationEntity();
                        }
                    }
                )
            );

        $mockFactory->method('getEntityManager')
            ->willReturn($mockEntityManager);

        $mockTranslator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getTranslator')
            ->willReturn($mockTranslator);

        $mockRouter = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getRouter')
            ->willReturn($mockRouter);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCompanyModel->method('getEntity')
            ->willReturn(
                new Company()
            );
        $mockFieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockNotificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFactory->method('getModel')
            ->will(
                $this->returnValueMap(
                    [
                        ['lead', $mockLeadModel],
                        ['lead.company', $mockCompanyModel],
                        ['lead.field', $mockFieldModel],
                        ['core.notification', $mockNotificationModel],
                    ]
                )
            );

        $mockLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getLogger')
            ->willReturn($mockLogger);

        $mockDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getDispatcher')
            ->willReturn($mockDispatcher);

        $mockCacheHelper = $this->getMockBuilder(CacheStorageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheHelper->method('getCache')
            ->willReturn($mockCacheHelper);

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

        $mockCacheHelper->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['leadFields.Lead', null, $leadFields],
                        ['leadFields.Contact', null, $contactFields],
                    ]
                )
            );

        $mockEncryptionHelper = $this->getMockBuilder(EncryptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockPathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getHelper')
            ->will(
                $this->returnValueMap(
                    [
                        ['cache_storage', $mockCacheHelper],
                        ['encryption', $mockEncryptionHelper],
                        ['paths', $mockPathsHelper],
                    ]
                )
            );

        return $mockFactory;
    }

    /**
     * @param int  $maxUpdate
     * @param int  $maxCreate
     * @param int  $maxSfLeads
     * @param int  $maxSfContacts
     * @param null $updateObject
     *
     * @return SalesforceIntegration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSalesforceIntegration($maxUpdate = 100, $maxCreate = 200, $maxSfLeads = 25, $maxSfContacts = 25, $updateObject = null)
    {
        $mockFactory = $this->getMockFactory();

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

        $sf = $this->getMockBuilder(SalesforceIntegration::class)
            ->setConstructorArgs([$mockFactory])
            ->setMethods($this->sfMockMethods)
            ->getMock();

        $sf->method('makeRequest')
            ->will(
                $this->returnCallback(
                    function () use ($maxSfContacts, $maxSfLeads) {
                        $args = func_get_args();

                        // Determine what to return by analyzing the URL and query parameters
                        switch (true) {
                            case strpos($args[0], '/query') !== false:
                                // Extract emails
                                preg_match('/Email in \(\'(.*?)\'\)/', $args[1]['q'], $match);
                                $emails = explode("','", $match[1]);

                                return $this->getSalesforceObjects($emails, $maxSfContacts, $maxSfLeads);
                            case strpos($args[0], '/composite') !== false:
                                return $this->getSalesforceCompositeResponse($args[1]);
                        }
                    }
                )
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject $mockDispatcher */
        $mockDispatcher = $mockFactory->getDispatcher();
        $mockDispatcher->method('dispatch')
            ->will(
                $this->returnCallback(
                    function () use ($sf, $integration) {
                        $args = func_get_args();

                        switch ($args[0]) {
                            default:
                                return new PluginIntegrationKeyEvent($sf, $integration->getApiKeys());
                        }
                    }
                )
            );

        $sf->setIntegrationSettings($integration);

        $repo = $sf->getIntegrationEntityRepository();
        $this->setLeadsToUpdate($repo, $maxUpdate, $maxSfContacts, $maxSfLeads, $updateObject);
        $this->setLeadsToCreate($repo, $maxCreate);

        return $sf;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $mockRepository
     * @param                                          $max
     * @param                                          $maxSfContacts
     * @param                                          $maxSfLeads
     * @param                                          $specificObject
     */
    protected function setLeadsToUpdate(\PHPUnit_Framework_MockObject_MockObject $mockRepository, $max, $maxSfContacts, $maxSfLeads, $specificObject)
    {
        $mockRepository->method('findLeadsToUpdate')
            ->will(
                $this->returnCallback(
                    function () use ($max, $specificObject, $maxSfContacts, $maxSfLeads) {
                        $args = func_get_args();
                        $object = $args[4];

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

                        $results = $this->getLeadsToUpdate($object, $args[3], $max, $specificObject);

                        return $results;
                    }
                )
            );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $mockRepository
     * @param int                                      $max
     */
    protected function setLeadsToCreate(\PHPUnit_Framework_MockObject_MockObject $mockRepository, $max = 200)
    {
        $mockRepository->method('findLeadsToCreate')
            ->will(
                $this->returnCallback(
                    function () use (&$restart, $max) {
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
                )
            );
    }

    /**
     * Simulate looping over Mautic leads to update.
     *
     * @param $object
     * @param $limit
     * @param $max
     * @param $specificObject
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
     * @param $start
     * @param $limit
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
            //Start after the update
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

        $this->returnedSfEntities = array_merge($this->returnedSfEntities, $records);

        return [
            'totalSize' => count($records),
            'done'      => true,
            'records'   => $records,
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
     * @param $data
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
                if (count($parts) === 3) {
                    list($contactId, $sfObject, $id) = $parts;
                } elseif (count($parts) === 2) {
                    list($contactId, $sfObject) = $parts;
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
}
