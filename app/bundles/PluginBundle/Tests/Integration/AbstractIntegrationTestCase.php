<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractIntegrationTestCase extends MauticMysqlTestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var CacheStorageHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var EncryptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encryptionHelper;

    /**
     * @var LeadModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $leadModel;

    /**
     * @var CompanyModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $companyModel;

    /**
     * @var PathsHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pathsHelper;

    /**
     * @var NotificationModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $notificationModel;

    /**
     * @var FieldModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldModel;

    /**
     * @var IntegrationEntityModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $integrationEntityModel;

    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->cache                  = $this->createMock(CacheStorageHelper::class);
        $this->em                     = $this->createMock(EntityManager::class);
        $this->session                = $this->createMock(Session::class);
        $this->request                = $this->createMock(RequestStack::class);
        $this->router                 = $this->createMock(Router::class);
        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->logger                 = $this->createMock(Logger::class);
        $this->encryptionHelper       = $this->createMock(EncryptionHelper::class);
        $this->leadModel              = $this->createMock(LeadModel::class);
        $this->companyModel           = $this->createMock(CompanyModel::class);
        $this->pathsHelper            = $this->createMock(PathsHelper::class);
        $this->notificationModel      = $this->createMock(NotificationModel::class);
        $this->fieldModel             = $this->createMock(FieldModel::class);
        $this->integrationEntityModel = $this->createMock(IntegrationEntityModel::class);
    }
}
