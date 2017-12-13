<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\WebinarBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use MauticPlugin\MauticWebinarBundle\Integration\WebikeoIntegration;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class WebikeoIntegrationTest.
 */
class WebikeoIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $persistedIntegrationEntities = [];

    /**
     * @var array
     */
    protected $mockMethods = [
        'makeRequest',
    ];

    public function testGetWebinars()
    {
    }

    public function testHasAttendedWebinar()
    {
        $this->mockMethods  = ['makeRequest'];
        $webinar['webinar'] = 1;
        //contact1 should return true
        $contact1 = new Lead();
        $contact1->setFirstname('FirstName');
        $contact1->setLastname('LastName');
        $contact1->setEmail('test@test.com');

        //contact2 should return false
        $contact2 = new Lead();
        $contact2->setFirstname('FirstName2');
        $contact2->setLastname('LastName2');
        $contact2->setEmail('test2@test.com');

        $webikeoIntegration = $this->getWebikeoIntegration();
        $webikeoIntegration->expects($this->any())
            ->method('makeRequest')
            ->with(
                'https://api.webikeo.com/v1/webinars/'.$webinar['webinar'].'/subscriptions'
            );

        $hasAttended = $webikeoIntegration->hasAttendedWebinar($webinar, $contact1);
        $this->assertEquals(true, $hasAttended);

        $hasAttended = $webikeoIntegration->hasAttendedWebinar($webinar, $contact2);
        $this->assertEquals(false, $hasAttended);
    }

    public function testSubscribeToWebinar()
    {
        $this->mockMethods  = ['makeRequest', 'formatContactData'];
        $webinar['webinar'] = 1;

        $contact1 = new Lead();
        $contact1->setFirstname('FirstName');
        $contact1->setLastname('LastName');
        $contact1->setEmail('test@test.com');
        $contact1->setId(1);

        $campaign = 'testCampaign';

        $postData = [
            'user' => [
                'email'     => null,
                'firstName' => null,
                'lastName'  => null,
            ],
            'trackingCampaign' => $campaign,
        ];

        $webikeoIntegration = $this->getWebikeoIntegration();
        $webikeoIntegration->expects($this->once())
            ->method('makeRequest')
            ->with(
                'https://api.webikeo.com/v1/webinars/'.$webinar['webinar'].'/subscriptions', $postData
            );

        $isSubscribed = $webikeoIntegration->subscribeToWebinar($webinar, $contact1, $campaign);

        $this->assertEquals(true, $isSubscribed);
    }

    public function getSubscriptions()
    {
        return [
            '_embedded'=> [
                'subscription'=> [
                    [
                        'source'       => '...',
                        'createdAt'    => '2017-01-26T09=>23=>23+0200',
                        'updatedAt'    => '2017-01-27T09=>23=>23+0200',
                        'engagedAt'    => '2017-01-27T09=>23=>23+0200',
                        'hasViewed'    => false,
                        'hasContacted' => false,
                        'hasDownloaded'=> false,
                        'hasRated'     => false,
                        'hasReplayed'  => false,
                        'rating'       => null,
                        'ratingComment'=> null,
                        'minutesLive'  => 0,
                        'minutesReplay'=> 0,
                        'id'           => 1,
                        'user'         => [
                            'email'       => 'test@test.com',
                            'firstName'   => 'FirstName',
                            'lastName'    => 'LastName',
                            'companyLabel'=> 'Webikeo',
                            'companySize' => [
                                'label'=> 'De 11 à 50 employés',
                                'id'   => 3,
                            ],
                            'functionLabel' => null,
                            'departmentName'=> 'relation-client',
                            'domainName'    => 'services-entreprises',
                            'countryLabel'  => 'France',
                            'country'       => [
                                'label'=> 'France',
                                'code' => 'FR',
                                'id'   => 73,
                            ],
                            'phone'   => null,
                            'id'      => 123456,
                            'language'=> 'fr',
                        ],
                        'subscriptionLiveCtaTrackings'=> [
                            [
                                'id'     => 109,
                                'liveCta'=> [
                                    'id'   => 5,
                                    'label'=> 'Je souhaite être rappelé',
                                ],
                                'clickedAt'=> '2017-01-010T10=>40=>11+0200',
                            ],
                        ],
                        'subscriptionFormFieldAnswers'=> [
                            [
                                'id'             => 1862,
                                'value'          => 'Oui',
                                'createdAt'      => '2017-01-06T09=>23=>23+0200',
                                'formFieldOption'=> [
                                    'id'   => 60,
                                    'label'=> 'Oui',
                                ],
                                'formField'=> [
                                    'id'   => 41,
                                    'label'=> 'Avez-vous déjà utilisé un outil de provisioning ?',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getSubscriptionResponse()
    {
        return [
            'source'       => 'api',
            'createdAt'    => '2017-11-06T14:07:28+0100',
            'updatedAt'    => null,
            'engagedAt'    => null,
            'hasViewed'    => false,
            'hasContacted' => false,
            'hasDownloaded'=> false,
            'hasRated'     => false,
            'hasReplayed'  => false,
            'rating'       => null,
            'ratingComment'=> null,
            'minutesLive'  => 0,
            'minutesReplay'=> 0,
            'id'           => 748012,
            'user'         => [
                'language'      => 'fr',
                'email'         => 'test@test.com',
                'firstName'     => 'FirstName',
                'lastName'      => 'LastName',
                'companyLabel'  => 'Webikeo',
                'companySize'   => null,
                'functionLabel' => 'Directeur Performance Media & Programmatic',
                'departmentName'=> 'marketing-communication',
                'countryLabel'  => 'France',
                'phone'         => '0600000000',
                'id'            => 191072,
                'country'       => [
                    'id'   => 1920,
                    'label'=> 'France',
                     'code'=> 'FR',
                ],
            ],
            'subscriptionLiveCtaTrackings'=> [],
            'subscriptionFormFieldAnswers'=> [],
        ];
    }

    protected function getWebikeoIntegration()
    {
        $mockFactory = $this->getMockFactory();

        $featureSettings = [
            'leadFields' => [
                'email'        => 'email',
                'firstName'    => 'firstname',
                'lastName'     => 'lastname',
                'companyLabel' => 'company',
            ],
        ];

        $response = [
            'token' => 'ABC',
        ];

        $integration = new Integration();
        $integration->setIsPublished(true)
            ->setName('Webikeo')
            ->setPlugin('MauticWebinarBundle')
            ->setApiKeys(
                $response
            )
            ->setFeatureSettings($featureSettings)
            ->setSupportedFeatures(
                [
                    'push_subscriptions', 'get_subscriptions',
                ]
            );
        $wi = $this->getMockBuilder(WebikeoIntegration::class)
            ->setConstructorArgs([$mockFactory])
            ->setMethods($this->mockMethods)
            ->getMock();

        $wi->method('makeRequest')
            ->will(
                $this->returnCallback(
                    function () {
                        $args = func_get_args();
                        // Determine what to return by analyzing the URL and query parameters
                        switch (true) {
                            case strpos($args[0], '/subscriptions') !== false:
                                if (isset($args[1]['user'])) {
                                    return $this->getSubscriptionResponse();
                                } else {
                                    return $this->getSubscriptions();
                                }

                            default:
                                return $this->getSubscriptions();
                        }

                        return [];
                    }
                )
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject $mockDispatcher */
        $mockDispatcher = $mockFactory->getDispatcher();
        $mockDispatcher->method('dispatch')
            ->will(
                $this->returnCallback(
                    function () use ($wi, $integration) {
                        $args = func_get_args();

                        switch ($args[0]) {
                            default:
                                return new PluginIntegrationKeyEvent($wi, $integration->getApiKeys());
                        }
                    }
                )
            );

        $wi->setIntegrationSettings($integration);

        return $wi;
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
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
            ->willReturnCallback(
                function () {
                    $this->persistedIntegrationEntities = array_merge($this->persistedIntegrationEntities, func_get_arg(0));
                }
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

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

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
}
