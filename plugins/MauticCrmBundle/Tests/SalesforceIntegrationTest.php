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

class SalesforceIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const SC_MULTIPLE_SF_LEADS = 'multiple_sf_leads';
    const SC_MULTIPLE_SF_CONTACTS = 'multiple_sf_contacts';
    const SC_CONVERTED_SF_LEAD = 'converted_sf_lead';
    const SC_EMAIL_WITH_APOSTROPHE = 'email_with_apostrophe';

    /**
     * @var
     */
    protected $specialSfCase;

    /**
     * @var array
     */
    protected $persistedIntegrationEntities = [];

    protected $returnedSfEntities = [];

    public function testPushLeadsUpdateAndCreateCorrectNumbers()
    {
        $sf   = $this->getSalesforceIntegration();
        $stats = $sf->pushLeads();

        // 100 Leads to update, 100 Contacts to update, 200 to create
        // Should have updated 25 per batch of 100; (400 total / 100 per batch) * 25 = 100 updates
        $this->assertEquals(100, $stats[0]);
        $this->assertEquals(300, $stats[1]);
        $this->assertEquals(400, count($this->getPersistedIntegrationEntities()));
    }

    public function testThatMultipleSfLeadsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated()
    {
        $this->specialSfCase = self::SC_MULTIPLE_SF_LEADS;
        $sf = $this->getSalesforceIntegration(2, 0, 2, 0, 'Lead');
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
        $sf = $this->getSalesforceIntegration(2, 0, 0, 2, 'Contact');
        $sf->pushLeads();

        // Validate there are only two integration entities (two contacts with same email)
        $integrationEntities = $this->getPersistedIntegrationEntities();
        $this->assertEquals(2, count($integrationEntities));

        // Validate that there were 4 found entries (two duplciate leads)
        $sfEntities = $this->getReturnedSfEntities();
        $this->assertEquals(4, count($sfEntities));
    }

    public function testThatConvertedLeadsHaveIntegrationEntityCreatedAndNotReCreated()
    {

    }

    public function testThatMultipleMauticContactsAreNotDuplicatedInSF()
    {

    }

    public function testThatLeadsAreOnlyCreatedIfEnabled()
    {

    }

    public function testThatLeadsAreOnlyCreatedIfLimitIsAppropriate()
    {

    }

    public function testThatMissingRequiredDataIsPulledFromSfAndHydrated()
    {

    }

    public function testProgressBarExistsScript()
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

        $mockEntityManager               = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockIntegrationEntityRepository = $this->getMockBuilder(IntegrationEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need insight into the entities persisted
        $mockIntegrationEntityRepository->method('saveEntities')
            ->will(
                $this->returnCallback(
                    function() {
                        $args = func_get_args();
                        $this->persistedIntegrationEntities= array_merge($this->persistedIntegrationEntities, $args[0]);
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
                    function() {
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

        $mockLeadModel         = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCompanyModel      = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCompanyModel->method('getEntity')
            ->willReturn(
                new Company()
            );
        $mockFieldModel        = $this->getMockBuilder(FieldModel::class)
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

        $leadFields    = [
            'Id__Lead'        =>
                [
                    'type'        => 'string',
                    'label'       => 'Lead-Lead ID',
                    'required'    => false,
                    'group'       => 'Lead',
                    'optionLabel' => 'Lead ID',
                ],
            'LastName__Lead'  =>
                [
                    'type'        => 'string',
                    'label'       => 'Lead-Last Name',
                    'required'    => true,
                    'group'       => 'Lead',
                    'optionLabel' => 'Last Name',
                ],
            'FirstName__Lead' =>
                [
                    'type'        => 'string',
                    'label'       => 'Lead-First Name',
                    'required'    => false,
                    'group'       => 'Lead',
                    'optionLabel' => 'First Name',
                ],
            'Company__Lead'   =>
                [
                    'type'        => 'string',
                    'label'       => 'Lead-Company',
                    'required'    => true,
                    'group'       => 'Lead',
                    'optionLabel' => 'Company',
                ],
            'Email__Lead'     =>
                [
                    'type'        => 'string',
                    'label'       => 'Lead-Email',
                    'required'    => false,
                    'group'       => 'Lead',
                    'optionLabel' => 'Email',
                ],
        ];
        $contactFields = [
            'Id__Contact'        =>
                [
                    'type'        => 'string',
                    'label'       => 'Contact-Contact ID',
                    'required'    => false,
                    'group'       => 'Contact',
                    'optionLabel' => 'Contact ID',
                ],
            'LastName__Contact'  =>
                [
                    'type'        => 'string',
                    'label'       => 'Contact-Last Name',
                    'required'    => true,
                    'group'       => 'Contact',
                    'optionLabel' => 'Last Name',
                ],
            'FirstName__Contact' =>
                [
                    'type'        => 'string',
                    'label'       => 'Contact-First Name',
                    'required'    => false,
                    'group'       => 'Contact',
                    'optionLabel' => 'First Name',
                ],
            'Email__Contact'     =>
                [
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
                        ['leadFields.Contact', null, $contactFields]
                    ]
                )
            );

        $mockEncryptionHelper = $this->getMockBuilder(EncryptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockPathsHelper      = $this->getMockBuilder(PathsHelper::class)
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSalesforceIntegration($maxUpdate = 100, $maxCreate = 200, $maxSfLeads = 25, $maxSfContacts = 25, $updateObject = null)
    {
        $mockFactory = $this->getMockFactory();

        $featureSettings = [
            'sandbox'               =>
                [
                ],
            'updateOwner'           =>
                [
                ],
            'objects'               =>
                [
                    0 => 'Lead',
                    1 => 'Contact',
                    2 => 'company',
                ],
            'namespace'             => null,
            'leadFields'            =>
                [
                    'Company__Lead'      => 'company',
                    'FirstName__Lead'    => 'firstname',
                    'LastName__Lead'     => 'lastname',
                    'Email__Lead'        => 'email',
                    'FirstName__Contact' => 'firstname',
                    'LastName__Contact'  => 'lastname',
                    'Email__Contact'     => 'email',

                ],
            'update_mautic'         =>
                [
                    'Company__Lead'      => '0',
                    'FirstName__Lead'    => '0',
                    'LastName__Lead'     => '0',
                    'Email__Lead'        => '0',
                    'FirstName__Contact' => '0',
                    'LastName__Contact'  => '0',
                    'Email__Contact'     => '0',
                ],
            'companyFields'         =>
                [
                    'Name' => 'companyname',
                ],
            'update_mautic_company' =>
                [
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
                    'push_leads'
                ]
            );

        $sf = $this->getMockBuilder(SalesforceIntegration::class)
            ->setConstructorArgs([$mockFactory])
            ->setMethods(['makeRequest'])
            ->getMock();

        $contactStart = 1;
        $leadStart =    1;
        $sf->method('makeRequest')
            ->will(
                $this->returnCallback(
                    function() use (&$contactStart, &$leadStart, $maxSfContacts, $maxSfLeads) {
                        $args = func_get_args();

                        // Determine what to return by analyzing the URL and query parameters
                        switch (true) {
                            case strpos($args[0], '/query') !== false:
                                // Check if Contact or Lead is being queried
                                $results = [];
                                switch (true) {
                                    case (strpos($args[1]['q'], 'from Contact')):
                                        // Contact IDs start at 200
                                        $results = $this->getSalesforceContacts($contactStart + 200, $maxSfContacts);
                                        // fetch 100 at a time so start at the next batch
                                        $contactStart += 100;
                                        break;
                                    case (strpos($args[1]['q'], 'from Lead')):
                                        $results =  $this->getSalesforceLeads($leadStart, $maxSfLeads);
                                        $leadStart += 100;
                                        break;
                                }

                                return $results;
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
                    function() use ($sf, $integration) {
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

    protected function setLeadsToUpdate(\PHPUnit_Framework_MockObject_MockObject $mockRepository, $max,  $maxSfContacts, $maxSfLeads, $specificObject )
    {
        $restart = true;
        $mockRepository->method('findLeadsToUpdate')
            ->will(
                $this->returnCallback(
                    function () use (&$restart, $max, $specificObject, $maxSfContacts, $maxSfLeads) {
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

                        static $start = 1;
                        if ($restart) {
                            $start = 1;
                        }

                        $results = $this->getLeadsToUpdate($object, $start, $args[3], $max, $specificObject);
                        $start += $args[3];

                        return $results;
                    }
                )
            );
    }

    protected function setLeadsToCreate(\PHPUnit_Framework_MockObject_MockObject $mockRepository, $max = 200)
    {
        $restart = true;
        $mockRepository->method('findLeadsToCreate')
            ->will(
                $this->returnCallback(
                    function () use (&$restart, $max) {

                        $args = func_get_args();

                        if (false === $args[2]) {
                            return $max;
                        }

                        static $start = 1;
                        if ($restart) {
                            $start = 1;
                        }
                        $restart = false;

                        $createLeads = $this->getLeadsToCreate($start, $args[2], $max);
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
     * Simulate looping over Mautic leads to update
     *
     * @param $start
     * @param $limit
     *
     * @return array
     */
    protected function getLeadsToUpdate($object, $start, $limit, $max, $specificObject)
    {
        $entities = [
            $object => [],
        ];

        // Should be 100 each
        if ($start >= $max || ($specificObject && $specificObject !== $object)) {
            return $entities;
        }

        if ($limit > $max) {
            $limit = $max;
        }

        $counter = 0;
        while ($counter < $limit) {
            $id = $start+$counter;
            $entities[$object][$id] = [
                'integration_entity_id' => 'SF'.$id,
                'integration_entity'    => $object,
                'id'                    => $id,
                'internal_entity_id'    => $id,
                'firstname'             => $object.$id,
                'lastname'              => $object.$id,
                'company'               => $object.$id,
                'email'                 => $object.$id.'@sftest.com',
            ];

            ++$counter;
        }

        return $entities;
    }

    /**
     * Simulate looping over Mautic leads to create
     *
     * @param $start
     * @param $limit
     *
     * @return array
     */
    protected function getLeadsToCreate($start, $limit, $max = 200)
    {
        $entities = [];
        if ($start >= $max) {
            return $entities;
        }

        if ($limit > $max) {
            $limit = $max;
        }

        $counter = 0;
        while ($counter < $limit) {
            //Start after the update
            $id = $start+200+$counter;
            $entities[$id] = [
                'id'                    => $id,
                'internal_entity_id'    => $id,
                'firstname'             => 'CreateLead'.$id,
                'lastname'              => 'CreateLead'.$id,
                'company'               => 'CreateLead'.$id,
                'email'                 => 'CreateLead'.$id.'@sftest.com',
            ];

            ++$counter;
        }

        return $entities;
    }

    /**
     * Mock SF response
     *
     * @return array
     */
    protected function getSalesforceContacts($id, $max)
    {
        // Let's find around $max records
        $records = [];
        $count = 0;
        while ($count < $max) {
            $records[] = [
                'attributes' =>
                    [
                        'type' => 'Contact',
                        'url'  => '/services/data/v34.0/sobjects/Contact/SF'.$id,
                    ],
                'Id'         => 'SF'.$id,
                'FirstName'  => 'Contact'.$id,
                'LastName'   => 'Contact'.$id,
                'Email'      => 'Contact'.$id.'@sftest.com',
            ];


            $this->addSpecialCases($id, $records);

            ++$count;
            ++$id;
        }

        $this->returnedSfEntities = array_merge($this->returnedSfEntities, $records);

        return [
            'totalSize' => $max,
            'done'      => true,
            'records'   => $records,
        ];
    }

    /**
     * Mock SF response
     *
     * @return array
     */
    protected function getSalesforceLeads($id, $max)
    {
        // Let's find around $max records
        $records = [];
        $count = 0;
        while ($count < $max) {
            $records[] = [
                'attributes'         =>
                    [
                        'type' => 'Lead',
                        'url'  => '/services/data/v34.0/sobjects/Lead/SF'.$id,
                    ],
                'Id'                 => 'SF'.$id,
                'FirstName'          => 'Lead'.$id,
                'LastName'           => 'Lead'.$id,
                'Email'              => 'Lead'.$id.'@sftest.com',
                'Company'            => 'Lead'.$id,
                'ConvertedContactId' => null,
            ];

            $this->addSpecialCases($id, $records);

            ++$id;
            ++$count;
        }

        $this->returnedSfEntities = array_merge($this->returnedSfEntities, $records);

        return [
            'totalSize' => $max,
            'done'      => true,
            'records'   => $records,
        ];
    }

    protected function addSpecialCases($id, &$records)
    {
        switch($this->specialSfCase) {
            case self::SC_MULTIPLE_SF_LEADS:
                $records[] = [
                    'attributes'         =>
                        [
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
                    'attributes'         =>
                        [
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
     * Mock SF response
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
                list($contactId, $sfObject) = $subrequest['referenceId'];
                $response[] = [
                    'body'           => [
                        'id'      => 'SF'.$contactId,
                        'success' => true,
                        'errors'  => [],
                    ],
                    'httpHeaders'    => [
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
        $entities = $this->persistedIntegrationEntities;
        $this->persistedIntegrationEntities = [];

        return $entities;
    }

    protected function getReturnedSfEntities()
    {
        $entities = $this->returnedSfEntities;
        $this->returnedSfEntities = [];

        return $entities;
    }

}