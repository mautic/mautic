<?php

namespace Mautic\UserBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserInvite;
use Mautic\UserBundle\Entity\UserInviteRepositoryInterface;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Exception\PasswordResetTokenCreationFailedException;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

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

    /**
     * @var MockObject&Environment
     */
    private MockObject $twig;

    protected function setUp(): void
    {
        $this->mailHelper       = $this->createMock(MailHelper::class);
        $this->userTokenService = $this->createMock(UserTokenServiceInterface::class);
        $this->entityManager    = $this->createMock(EntityManager::class);
        $this->user             = $this->createMock(User::class);
        $this->router           = $this->createMock(Router::class);
        $this->translator       = $this->createMock(Translator::class);
        $this->userToken        = $this->createMock(UserToken::class);
        $this->logger           = $this->createMock(LoggerInterface::class);
        $this->twig             = $this->createMock(Environment::class);

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
            $this->createMock(CoreParametersHelper::class),
            $this->twig
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
        $errorMessage = 'Database connection failed';

        $this->expectException(PasswordResetTokenCreationFailedException::class);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Doctrine\DBAL\Exception($errorMessage));

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['mautic.user.password.reset.token.creation.database.error', [], 'messages', null, 'Database error during password reset token creation'],
                ['mautic.user.password.reset.token.creation.failed', [], null, null, 'Failed to create password reset token'],
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Database error during password reset token creation: '.$errorMessage);

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

    public function testCreateInviteStoresInviteAndSendsTemplatedEmail(): void
    {
        $email     = 'invitee@example.com';
        $link      = 'https://mautic.example/invite/token';
        $role      = new Role();

        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('revokeOutstandingInvites')
            ->with($email);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (UserInvite $invite) use ($email, $role): bool {
                return $email === $invite->getEmail()
                    && 32 === strlen((string) $invite->getTokenSelector())
                    && str_starts_with((string) $invite->getTokenVerifierHash(), '$')
                    && $role === $invite->getRole()
                    && $invite->getExpiration() > new \DateTime();
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_user_invite_register', $this->isType('array'), UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($link);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['mautic.user.invite.subject', [], null, null, 'Invite subject'],
                ['mautic.user.invite.email.body', ['%invite_link%' => $link], null, null, 'Invite body '.$link],
            ]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@MauticUser/Email/invite.html.twig', ['inviteLink' => $link])
            ->willReturn('<p>Invite html</p>');

        $this->mailHelper->expects($this->once())
            ->method('getMailer')
            ->willReturn($this->mailHelper);

        $this->mailHelper->expects($this->once())
            ->method('setTo')
            ->with([$email => $email]);

        $this->mailHelper->expects($this->once())
            ->method('setSubject')
            ->with('Invite subject');

        $this->mailHelper->expects($this->once())
            ->method('setBody')
            ->with('<p>Invite html</p>');

        $this->mailHelper->expects($this->once())
            ->method('setPlainText')
            ->with('Invite body '.$link);

        $this->mailHelper->expects($this->once())
            ->method('send');

        $invite = $this->userModel->createInvite($email, $role);

        $this->assertSame($email, $invite->getEmail());
        $this->assertSame($role, $invite->getRole());
    }

    public function testHasUserWithEmailReturnsWhetherUserExists(): void
    {
        $email      = 'invitee@example.com';
        $repository = $this->createMock(UserRepository::class);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(new User());

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $this->assertTrue($this->userModel->hasUserWithEmail($email));
    }

    public function testGetInviteReturnsNullWhenInviteDoesNotExist(): void
    {
        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('findOneByTokenSelector')
            ->with('missing-selector')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User invite link rejected: token selector was not found', ['selector' => 'missing-selector']);

        $this->assertNull($this->userModel->getInvite('missing-selector.verifier'));
    }

    public function testGetInviteReturnsNullWhenInviteExpired(): void
    {
        $invite = (new UserInvite(new Role()))
            ->setExpiration(new \DateTimeImmutable('-1 minute'));

        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('findOneByTokenSelector')
            ->with('expired-selector')
            ->willReturn($invite);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User invite link rejected: invite has expired', ['invite_id' => null, 'email' => null]);

        $this->assertNull($this->userModel->getInvite('expired-selector.verifier'));
    }

    public function testGetInviteReturnsNullWhenTokenVerifierDoesNotMatch(): void
    {
        $invite = (new UserInvite(new Role()))
            ->setTokenVerifierHash(password_hash('expected-verifier', PASSWORD_DEFAULT))
            ->setExpiration(new \DateTimeImmutable('+1 minute'));

        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('findOneByTokenSelector')
            ->with('active-selector')
            ->willReturn($invite);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User invite link rejected: token verifier did not match', ['invite_id' => null, 'email' => null]);

        $this->assertNull($this->userModel->getInvite('active-selector.wrong-verifier'));
    }

    public function testGetInviteReturnsNullWhenInviteAlreadyUsed(): void
    {
        $invite = (new UserInvite(new Role()))
            ->setUsed(true)
            ->setTokenVerifierHash(password_hash('verifier', PASSWORD_DEFAULT))
            ->setExpiration(new \DateTimeImmutable('+1 minute'));

        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('findOneByTokenSelector')
            ->with('used-selector')
            ->willReturn($invite);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User invite link rejected: invite has already been used', ['invite_id' => null, 'email' => null]);

        $this->assertNull($this->userModel->getInvite('used-selector.verifier'));
    }

    public function testGetInviteReturnsNullWhenTokenFormatIsInvalid(): void
    {
        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User invite link rejected: token format is invalid', []);

        $this->assertNull($this->userModel->getInvite('invalid-token'));
    }

    public function testGetInviteReturnsActiveInvite(): void
    {
        $invite = (new UserInvite(new Role()))
            ->setTokenVerifierHash(password_hash('verifier', PASSWORD_DEFAULT))
            ->setExpiration(new \DateTimeImmutable('+1 minute'));

        $inviteRepository = $this->createMock(UserInviteRepositoryInterface::class);
        $inviteRepository->expects($this->once())
            ->method('findOneByTokenSelector')
            ->with('active-selector')
            ->willReturn($invite);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(UserInvite::class)
            ->willReturn($inviteRepository);

        $this->assertSame($invite, $this->userModel->getInvite('active-selector.verifier'));
    }

    public function testMarkInviteUsedPersistsInvite(): void
    {
        $invite = new UserInvite(new Role());

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($invite);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userModel->markInviteUsed($invite);

        $this->assertTrue($invite->isUsed());
    }
}
