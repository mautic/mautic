<?php

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Model\UserToken\UserTokenService;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserModelTest extends TestCase
{
    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var MailHelper
     */
    private $mailHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var User
     */
    private $user;

    /**
     * @var UserToken
     */
    private $userToken;

    /**
     * @var UserTokenService
     */
    private $userTokenService;

    /**
     * @var Logger
     */
    private $logger;

    public function setUp(): void
    {
        $this->mailHelper       = $this->createMock(MailHelper::class);
        $this->userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $this->entityManager    = $this->createMock(EntityManager::class);
        $this->user             = $this->createMock(User::class);
        $this->router           = $this->createMock(Router::class);
        $this->translator       = $this->createMock(TranslatorInterface::class);
        $this->userToken        = $this->createMock(UserToken::class);
        $this->logger           = $this->createMock(Logger::class);

        $this->userModel = new UserModel($this->mailHelper, $this->userTokenService);
        $this->userModel->setEntityManager($this->entityManager);
        $this->userModel->setRouter($this->router);
        $this->userModel->setTranslator($this->translator);
        $this->userModel->setLogger($this->logger);
    }

    public function testThatItSendsResetPasswordEmailAndRouterGetsCalledWithCorrectParamters(): void
    {
        $this->userTokenService->expects($this->once())
            ->method('generateSecret')
            ->willReturn($this->userToken);

        $this->mailHelper->expects($this->once())
            ->method('getMailer')
            ->willReturn($this->mailHelper);

        $this->mailHelper->expects($this->once())
            ->method('send');

        $this->userTokenService->expects($this->once())
            ->method('generateSecret')
            ->willReturn($this->userToken);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_user_passwordresetconfirm', ['token' => null], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->userModel->sendResetEmail($this->user);
    }

    public function testThatDatabaseErrorThrowsRuntimeExceptionAndItIsLoggedWhenWeTryToSaveTokenToTheDatabaseWhenWeSendResetPasswordEmail(): void
    {
        $errorMessage = 'Some error message';

        $this->expectException(\RuntimeException::class);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception($errorMessage));

        $this->logger->expects($this->once())
            ->method('addError')
            ->with($errorMessage);

        $this->userModel->sendResetEmail($this->user);
    }
}
