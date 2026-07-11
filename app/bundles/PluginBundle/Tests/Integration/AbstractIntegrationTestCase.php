<?php

declare(strict_types=1);

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
     * @var MockObject&EventDispatcherInterface
     */
    protected MockObject $dispatcher;

    /**
     * @var MockObject&CacheStorageHelper
     */
    protected MockObject $cache;

    /**
     * @var MockObject&EntityManager
     */
    protected MockObject $em;

    /**
     * @var MockObject&Session
     */
    protected MockObject $session;

    /**
     * @var MockObject&RequestStack
     */
    protected MockObject $request;

    /**
     * @var MockObject&Router
     */
    protected MockObject $router;

    /**
     * @var MockObject&TranslatorInterface
     */
    protected MockObject $translator;

    /**
     * @var MockObject&Logger
     */
    protected MockObject $logger;

    /**
     * @var MockObject&EncryptionHelper
     */
    protected MockObject $encryptionHelper;

    /**
     * @var MockObject&LeadModel
     */
    protected MockObject $leadModel;

    /**
     * @var MockObject&CompanyModel
     */
    protected MockObject $companyModel;

    /**
     * @var MockObject&PathsHelper
     */
    protected MockObject $pathsHelper;

    /**
     * @var MockObject&NotificationModel
     */
    protected MockObject $notificationModel;

    /**
     * @var MockObject&FieldModel
     */
    protected MockObject $fieldModel;

    /**
     * @var MockObject&IntegrationEntityModel
     */
    protected MockObject $integrationEntityModel;

    /**
     * @var MockObject&DoNotContact
     */
    protected MockObject $doNotContact;

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
