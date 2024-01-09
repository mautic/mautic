<?php

namespace Mautic\PluginBundle\Tests\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractIntegrationTestCase extends TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var CacheStorageHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * @var Router|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $router;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var EncryptionHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $encryptionHelper;

    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $leadModel;

    /**
     * @var CompanyModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $companyModel;

    /**
     * @var PathsHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pathsHelper;

    /**
     * @var NotificationModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $notificationModel;

    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldModel;

    /**
     * @var IntegrationEntityModel|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $integrationEntityModel;

    /**
     * @var DoNotContact|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doNotContact;

    /**
     * @var MockObject&FieldsWithUniqueIdentifier
     */
    protected MockObject $fieldsWithUniqueIdentifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher                 = $this->createMock(EventDispatcherInterface::class);
        $this->cache                      = $this->createMock(CacheStorageHelper::class);
        $this->em                         = $this->createMock(EntityManager::class);
        $this->session                    = $this->createMock(Session::class);
        $this->request                    = $this->createMock(RequestStack::class);
        $this->router                     = $this->createMock(Router::class);
        $this->translator                 = $this->createMock(TranslatorInterface::class);
        $this->logger                     = $this->createMock(Logger::class);
        $this->encryptionHelper           = $this->createMock(EncryptionHelper::class);
        $this->leadModel                  = $this->createMock(LeadModel::class);
        $this->companyModel               = $this->createMock(CompanyModel::class);
        $this->pathsHelper                = $this->createMock(PathsHelper::class);
        $this->notificationModel          = $this->createMock(NotificationModel::class);
        $this->fieldModel                 = $this->createMock(FieldModel::class);
        $this->integrationEntityModel     = $this->createMock(IntegrationEntityModel::class);
        $this->doNotContact               = $this->createMock(DoNotContact::class);
        $this->fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);
    }
}
