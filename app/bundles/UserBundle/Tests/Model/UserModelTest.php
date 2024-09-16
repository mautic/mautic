<?php

namespace Mautic\UserBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserModelTest extends TestCase
{
    private UserModel $userModel;

    /**
     * @var MockObject&MailHelper
     */
    private $mailHelper;

    /**
     * @var MockObject&EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject&Router
     */
    private $router;

    /**
     * @var MockObject&TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject&User
     */
    private $user;

    /**
     * @var MockObject&UserToken
     */
    private $userToken;

    /**
     * @var MockObject&UserTokenServiceInterface
     */
    private $userTokenService;

    /**
     * @var MockObject&Logger
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

        $this->mailHelper
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

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->willReturn('test');

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

    public function testEmailUser(): void
    {
        $email   = 'a@test.com';
        $name    = 'name';
        $toMail  = [$email => $name];
        $subject = 'subject';
        $content = 'content';

        $this->user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->user->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->mailHelper->expects($this->once())
            ->method('getMailer')
            ->willReturn($this->mailHelper);

        $this->mailHelper->expects($this->once())
            ->method('setTo')
            ->with($toMail)
            ->willReturn(true);

        $this->mailHelper->expects($this->once())
            ->method('send');

        // Means no erros.
        $this->userModel->emailUser($this->user, $subject, $content);
    }

    public function testSendMailToEmailAddresses(): void
    {
        $toMails = ['a@test.com', 'b@test.com'];
        $subject = 'subject';
        $content = 'content';

        $this->mailHelper->expects($this->once())
            ->method('getMailer')
            ->willReturn($this->mailHelper);

        $this->mailHelper->expects($this->once())
            ->method('setTo')
            ->with($toMails)
            ->willReturn(true);

        $this->mailHelper->expects($this->once())
            ->method('send');

        // Means no erros.
        $this->userModel->sendMailToEmailAddresses($toMails, $subject, $content);
    }
}
