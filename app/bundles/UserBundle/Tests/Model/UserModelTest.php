<?php

namespace Mautic\UserBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserModelTest extends TestCase
{
    private UserModel $userModel;

    /**
     * @var MockObject&MailHelper
     */
    private MockObject $mailHelper;

    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManager;

    /**
     * @var MockObject&Router
     */
    private MockObject $router;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&User
     */
    private MockObject $user;

    /**
     * @var MockObject&UserToken
     */
    private MockObject $userToken;

    /**
     * @var MockObject&UserTokenServiceInterface
     */
    private MockObject $userTokenService;

    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $logger;

    public function setUp(): void
    {
        $this->mailHelper       = $this->createMock(MailHelper::class);
        $this->userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $this->entityManager    = $this->createMock(EntityManager::class);
        $this->user             = $this->createMock(User::class);
        $this->router           = $this->createMock(Router::class);
        $this->translator       = $this->createMock(Translator::class);
        $this->userToken        = $this->createMock(UserToken::class);
        $this->logger           = $this->createMock(LoggerInterface::class);

        $this->userModel = new UserModel(
            $this->mailHelper,
            $this->userTokenService,
            $this->entityManager,
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->router,
            $this->translator,
            $this->createMock(UserHelper::class),
            $this->logger,
            $this->createMock(CoreParametersHelper::class)
        );
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
            ->method('error')
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
