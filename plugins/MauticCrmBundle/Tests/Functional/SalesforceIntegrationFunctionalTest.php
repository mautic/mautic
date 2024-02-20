<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Request;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\Notification;
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
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Helper\DatabaseSchemaTrait;
use MauticPlugin\MauticCrmBundle\Tests\Helper\FixtureObjectsTrait;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Router;

final class SalesforceIntegrationFunctionalTest extends WebTestCase
{
    use DatabaseSchemaTrait, FixtureObjectsTrait;

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

    public function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
        $this->createFreshDatabaseSchema($this->getEntityManager());

        $fixturesDirectory   = $this->getFixturesDirectory('MauticCrmBundle');
        $objects             = $this->loadFixtureFiles([
            $fixturesDirectory.'/roles.yml',
            $fixturesDirectory.'/users.yml',
            $fixturesDirectory.'/leads.yml',
        ], false, null, 'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        parent::setUp();
    }

    public function testGetLeads()
    {
        $executed    = null;
        $integration = $this->getSalesforceIntegration();
        $result      = $integration->getLeads(['start' => '2019-01-01', 'end'=>date('Y-m-d 23:59:59')], null, $executed, null, 'Lead');

        $this->assertEquals([0, 0], $result);

        $result = $integration->getLeads(['start' => '2019-01-01', 'end'=>date('Y-m-d 23:59:59')], null, $executed, null, 'Contact');
    }

    private function getAPIMock($integration)
    {
        $APIMock = $this->getMockBuilder(SalesforceApi::class)
            ->setMethods(['makeRequest'])
            ->setConstructorArgs([$integration])
            ->getMock();

        return $APIMock;
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|SalesforceIntegration $sf */
        $sf = $this->getMockBuilder(SalesforceIntegration::class)
            ->setConstructorArgs([$mockFactory])
            ->setMethods(['request', 'isAuthorized', 'getApiHelper', 'makeRequest'])
            ->getMock();

        $integrationEntityModelMock = $this->getMockBuilder(IntegrationEntityModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integrationEntityModelMock->method('getEntityByIdAndSetSyncDate')
            ->willReturn(new IntegrationEntity());

        $sf->setIntegrationEntityModel($integrationEntityModelMock);

        $sf->method('request')
            ->will(
                $this->returnCallback(
                    function () use ($maxSfContacts, $maxSfLeads, $updateObject) {
                    }
                )
            );

        $sf->method('isAuthorized')->willReturn(true);
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
        $sf->method('getApiHelper')->willReturn($this->getAPIMock($sf));
        $sf->method('makeRequest')->willReturnCallback(function ($requestUrl, $elementData, $method, $settings) {
            $this->assertStringStartsWith('https://sftest.com', $requestUrl);
            $args = func_get_args();
            // Determine what to return by analyzing the URL and query parameters
            switch (true) {
                case strpos($args[0], '/query') !== false:
                    if (isset($args[1]['q']) && strpos($args[1]['q'], 'from Campaign') !== false) {
                        return [
                            'totalSize' => 0,
                            'records'   => [],
                        ];
                    } elseif (isset($args[1]['q']) && strpos($args[1]['q'], 'from Account') !== false) {
                        return [
                            'totalSize' => 0,
                            'records'   => [],
                        ];
                    } elseif (isset($args[1]['q']) && $args[1]['q'] === 'SELECT CreatedDate from Organization') {
                        return [
                            'records' => [
                                ['CreatedDate' => '2012-10-30T17:56:50.000+0000'],
                            ],
                        ];
                    } elseif (isset($args[1]['q']) && strpos($args[1]['q'], 'from Lead') !== false) {
                        if (strpos($args[1]['q'], 'HasOptedOutOfEmail') !== false) {
                            $errorMessage = "
SELECT Company, Email, LastName, HasOptedOutOfEmail, Id from Lead
                                 ^
ERROR at Row:1:Column:34
No such column 'HasOptedOutOfEmail' on entity 'Lead'. If you are attempting to use a custom field, be sure to append the '__c' after the custom field name. Please reference your WSDL or the describe call for the appropriate names.";
                            throw new ApiErrorException($errorMessage);
                        } else {
                            $response = [
                                'totalSize' => 0,
                                'records'   => [],
                            ];

                            return $response;
                        }
                    } elseif (isset($args[1]['q']) && strpos($args[1]['q'], 'from Contact') !== false) {
                        $response = [
                            'totalSize' => 1,
                            'done'      => true,
                            'records'   => [
                                [
                                    'Email'             => 'sample@contact.net',
                                    'LastName'          => 'Contact',
                                    'HasOptedOutOfEmail'=> true,
                                    'Id'                => '0034H00001tsgLEQAY',
                                ],
                            ],
                        ];

                        return $response;
                    }
                case strpos($args[0], '/composite') !== false:
                    return $this->getSalesforceCompositeResponse($args[1]);
            }
        });

        return $sf;
    }

    private function getLeadFormattedForAPI()
    {
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockFactory()
    {
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
            ->willReturnCallback(
                function () {
                    $this->persistedIntegrationEntities = array_merge($this->persistedIntegrationEntities, func_get_arg(0));
                }
            );

        $mockIntegrationEntityRepository
            ->expects($spy = $this->any())
            ->method('getIntegrationsEntityId')
            ->willReturnCallback(
                function () use ($spy) {
                    if (count($spy->getInvocations()) > $this->getMaxInvocations('getIntegrationsEntityId')) {
                        return null;
                    }

                    // Just return some bogus entities for testing
                    return $this->getLeadsToUpdate('Lead', 2, 2, 'Lead')['Lead'];
                }
            );
        $mockAuditLogRepo = $this->getMockBuilder(AuditLogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAuditLogRepo
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

        $mockEntityManager->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticPluginBundle:IntegrationEntity', $mockIntegrationEntityRepository],
                        ['MauticCoreBundle:AuditLog', $mockAuditLogRepo],
                        [User::class, $this->getEntityManager()->getRepository(User::class)],
                        ['MauticUserBundle:Role', $this->getEntityManager()->getRepository('MauticUserBundle:Role')],
                    ]
                )
            );

        $mockEntityManager->method('getReference')
            ->willReturnCallback(
                function () {
                    switch (func_get_arg(0)) {
                        case 'MauticPluginBundle:IntegrationEntity':
                            return new IntegrationEntity();
                    }
                }
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
            ->setMethods(['generate'])
            ->getMock();

        $mockRouter->method('generate')
            ->willReturnArgument(0);

        $mockFactory->method('getRouter')
            ->willReturn($mockRouter);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLeadModel->method('getEntity')
            ->willReturn(new Lead());
        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCompanyModel->method('getEntity')
            ->willReturn(new Company());
        $mockCompanyModel->method('getEntities')
            ->willReturn([]);
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

        $mockNotificationModel->method('getRepository')->willReturn($this->getEntityManager()->getRepository(Notification::class));

        return $mockFactory;
    }
}
