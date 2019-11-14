<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 6/20/2017
 * Time: 7:43 AM.
 */

namespace MauticCrmBundle\Api;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use MauticPlugin\MauticCrmBundle\Api\DynamicsApi;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\DataCollectorTranslator;

class DynamicsApiTest extends \PHPUnit_Framework_TestCase
{
    /** @var DynamicsApi */
    private $api;

    /** @var DynamicsIntegration */
    private $integration;

    private $dispatcher;
    private $cache;
    private $em;
    private $session;
    private $request;
    private $router;
    private $translator;
    private $logger;
    private $encryptionHelper;
    private $leadModel;
    private $companyModel;
    private $pathsHelper;
    private $notificationModel;
    private $fieldModel;
    private $integrationEntityModel;

    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->cache                  = $this->createMock(CacheStorageHelper::class);
        $this->em                     = $this->createMock(EntityManager::class);
        $this->session                = $this->createMock(Session::class);
        $this->request                = $this->createMock(RequestStack::class);
        $this->router                 = $this->createMock(Router::class);
        $this->translator             = $this->createMock(DataCollectorTranslator::class);
        $this->logger                 = $this->createMock(Logger::class);
        $this->encryptionHelper       = $this->createMock(EncryptionHelper::class);
        $this->leadModel              = $this->createMock(LeadModel::class);
        $this->companyModel           = $this->createMock(CompanyModel::class);
        $this->pathsHelper            = $this->createMock(PathsHelper::class);
        $this->notificationModel      = $this->createMock(NotificationModel::class);
        $this->fieldModel             = $this->createMock(FieldModel::class);
        $this->integrationEntityModel = $this->createMock(IntegrationEntityModel::class);

        $this->integration = new DynamicsIntegration(
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
            $this->integrationEntityModel
        );

        $this->api         = new DynamicsApi($this->integration);
    }

    public function testIntegration()
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }

    public function testGetLeads()
    {
    }

    public function testGetLeadFields()
    {
    }

    public function testCompanies()
    {
    }

    public function testCreateLead()
    {
    }

    public function testRequest()
    {
    }
}
