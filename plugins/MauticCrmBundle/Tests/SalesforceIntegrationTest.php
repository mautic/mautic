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
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Router;

class SalesforceIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testThatMultipleSfLeadsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated()
    {
        $sf   = $this->getSalesforceIntegration();
        $repo = $sf->getIntegrationEntityRepository();
        $this->setLeadsToUpdate($repo);
        $this->setLeadsToCreate($repo);

        $stats = $sf->pushLeads();
        die(var_dump($stats));
        $this->assertEmpty(array_sum($stats));
    }

    public function testThatMultipleSfContactsReturnedAreUpdatedButOnlyOneIntegrationRecordIsCreated()
    {

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
        $mockEntityManager->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticPluginBundle:IntegrationEntity', $mockIntegrationEntityRepository],
                    ]
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
     * @return SalesforceIntegration
     */
    protected function getSalesforceIntegration()
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

        $sf->method('makeRequest')
            ->will(
                $this->returnCallback(
                    function() {
                        $args = func_get_args();

                        // Determine what to return by analyzing the URL and query parameters
                        switch (true) {
                            case strpos($args[0], '/query') !== false:
                                // Check if Contact or Lead is being queried
                                switch (true) {
                                    case (strpos($args[1]['q'], 'from Contact')):
                                        return $this->getSalesforceContacts();
                                    case (strpos($args[1]['q'], 'from Lead')):
                                        return $this->getSalesforceLeads();
                                }
                                break;
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

        return $sf;
    }

    protected function setLeadsToUpdate(\PHPUnit_Framework_MockObject_MockObject $mockRepository)
    {
        $mockRepository->method('findLeadsToUpdate')
            ->will(
                $this->returnCallback(
                    function () {
                        $args = func_get_args();

                        // determine whether to return a count or records
                        $results = [];
                        $leadsToUpdate = $this->getLeadsToUpdate();
                        if (false === $args[3]) {
                            foreach ($args[4] as $object) {
                                $results[$object] = count($leadsToUpdate[$object]);
                            }

                            return $results;
                        }

                        foreach ((array) $args[4] as $object) {
                            $results[$object] = $leadsToUpdate[$object];
                        }

                        return $results;
                    }
                )
            );
    }

    protected function setLeadsToCreate(\PHPUnit_Framework_MockObject_MockObject $mockRepository)
    {
        $mockRepository->method('findLeadsToCreate')
            ->will(
                $this->returnCallback(
                    function () {
                        $args = func_get_args();

                        $createLeads = $this->getLeadsToCreate();
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
     * @param int $count
     */
    protected function getLeadsToUpdate($count = 1000)
    {
        $entities = [
            'Lead' => [],
            'Contact' => []
        ];

        $object = 'Lead';
        while ($count) {
            $entities[$object][$count] = [
                'integration_entity_id' => 'SF'.$count,
                'integration_entity'    => $object,
                'id'                    => $count,
                'internal_entity_id'    => $count,
                'firstname'             => $object.$count,
                'lastname'              => $object.$count,
                'company'               => $object.$count,
                'email'                 => $object.$count.'@sftest.com',
            ];

            $object = ('Lead' === $object) ? 'Contact' : 'Lead';
            --$count;
        }

        return $entities;
    }

    protected function getLeadsToCreate($count = 1000, $startingId = 2000)
    {
        $entities = [];

        while ($count) {
            $entities[$startingId] = [
                'id'                    => $startingId,
                'internal_entity_id'    => $startingId,
                'firstname'             => 'CreateLead'.$startingId,
                'lastname'              => 'CreateLead'.$startingId,
                'company'               => 'CreateLead'.$startingId,
                'email'                 => 'CreateLead'.$startingId.'@sftest.com',
            ];

            --$count;
            --$startingId;
        }

        return $entities;
    }

    /**
     * Mock SF response
     *
     * @return array
     */
    protected function getSalesforceContacts()
    {
        // Let's find around 25 records
        $records = [];
        $count = 0;
        while ($count < 25) {
            $records[] = [
                'attributes' =>
                    [
                        'type' => 'Contact',
                        'url'  => '/services/data/v34.0/sobjects/Contact/SF'.$count,
                    ],
                'Id'         => 'SF'.$count,
                'FirstName'  => 'Contact'.$count,
                'LastName'   => 'Contact'.$count,
                'Email'      => 'Contact'.$count.'@sftest.com',
            ];
            $count++;
        }

        return [
            'totalSize' => 25,
            'done'      => true,
            'records'   => $records,
        ];
    }

    /**
     * Mock SF response
     *
     * @return array
     */
    protected function getSalesforceLeads()
    {
        // Let's find around 25 records
        $records = [];
        $count = 25;
        while ($count < 50) {
            $records[] = [
                'attributes'         =>
                    [
                        'type' => 'Lead',
                        'url'  => '/services/data/v34.0/sobjects/Lead/SF'.$count,
                    ],
                'Id'                 => 'SF'.$count,
                'FirstName'          => 'Lead'.$count,
                'LastName'           => 'Lead'.$count,
                'Email'              => 'Lead'.$count.'@sftest.com',
                'Company'            => 'Lead'.$count,
                'ConvertedContactId' => null,
            ];
            $count++;
        }

        return [
            'totalSize' => 25,
            'done'      => true,
            'records'   => $records,
        ];
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
                        'Location' => '/services/data/v38.0/sobjects/Lead/SF'.$contactId,
                    ],
                    'httpStatusCode' => 201,
                    'referenceId'    => $subrequest['referenceId'],
                ];
            }
        }

        return $response;
    }
}