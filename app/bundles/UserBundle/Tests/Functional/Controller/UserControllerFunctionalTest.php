<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Controller\UserController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testEditUserAction(): void
    {
        $auditLogModel      = $this->createMock(AuditLogModel::class);
        $auditLogRepository = $this->createMock(AuditLogRepository::class);

        $auditLog = $this->auditLogSetter(1, 'Test User', 'user', 'security', 1, 'login', ['username' => 'testuser']);

        $auditLogRepository->method('getLogsForUser')->willReturn([$auditLog]);
        $auditLogModel->method('getRepository')->willReturn($auditLogRepository);

        $userModel = $this->createMock(UserModel::class);

        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@email.com');
        $user->setPassword('password');

        $userModel->method('getEntity')->willReturn($user);

        $languageHelper       = $this->createMock(LanguageHelper::class);
        $hasher               = $this->createMock(UserPasswordHasherInterface::class);
        $formFactory          = $this->createMock(FormFactoryInterface::class);
        $fieldHelper          = $this->createMock(FormFieldHelper::class);
        $managerRegistry      = $this->createMock(ManagerRegistry::class);
        $factory              = $this->createMock(MauticFactory::class);
        $modelFactory         = $this->createMock(ModelFactory::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $translator           = $this->createMock(Translator::class);
        $flashBag             = $this->createMock(FlashBag::class);
        $requestStack         = $this->createMock(RequestStack::class);
        $security             = $this->createMock(CorePermissions::class);

        $controller = new UserController($formFactory, $fieldHelper, $managerRegistry,
            $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher,
            $translator, $flashBag, $requestStack, $security);

        $response = $controller->editAction(new Request(), $languageHelper, $hasher, 1, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('testuser', $response->getContent());
    }

    public function auditLogSetter(int $userId, string $userName, string $bundle,
        string $object, int $objectId, string $action, array $details): AuditLog
    {
        $auditLog = new AuditLog();
        $auditLog->setUserId($userId);
        $auditLog->setUserName($userName);
        $auditLog->setBundle($bundle);
        $auditLog->setObject($object);
        $auditLog->setObjectId($objectId);
        $auditLog->setAction($action);
        $auditLog->setDetails($details);

        return $auditLog;
    }
}
