<?php

namespace Mautic\UserBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserInvite;
use Mautic\UserBundle\Entity\UserInviteRepositoryInterface;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Enum\UserTokenAuthorizator;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\Exception\PasswordResetTokenCreationFailedException;
use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use Mautic\UserBundle\UserEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * @extends FormModel<User>
 */
class UserModel extends FormModel implements GlobalSearchInterface
{
    private const INVITE_TOKEN_SELECTOR_BYTES = 16;

    private const INVITE_TOKEN_VERIFIER_BYTES = 32;

    public function __construct(
        protected MailHelper $mailHelper,
        private UserTokenServiceInterface $userTokenService,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private Environment $twig,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): UserRepository
    {
        return $this->em->getRepository(User::class);
    }

    public function getPermissionBase(): string
    {
        return 'user:users';
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], $this->translator->trans('mautic.user.entity.must.be.user', [], 'validators'));
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Get a list of users for an autocomplete input.
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param array  $permissionLimiter
     *
     * @return array
     */
    public function getUserList($search = '', $limit = 10, $start = 0, $permissionLimiter = [])
    {
        return $this->getRepository()->getUserList($search, $limit, $start, $permissionLimiter);
    }

    /**
     * Checks for a new password and rehashes if necessary.
     *
     * @param string     $submittedPassword
     * @param bool|false $validate
     */
    public function checkNewPassword(User $entity, UserPasswordHasherInterface $hasher, $submittedPassword, $validate = false): ?string
    {
        if ($validate) {
            if (strlen($submittedPassword) < 6) {
                throw new \InvalidArgumentException($this->translator->trans('mautic.user.user.password.minlength', [], 'validators'));
            }
        }

        if (!empty($submittedPassword)) {
            // hash the clear password submitted via the form
            return $hasher->hashPassword($entity, $submittedPassword);
        }

        return $entity->getPassword();
    }

    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], $this->translator->trans('mautic.user.entity.must.be.user', [], 'validators'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(UserType::class, $entity, $options);
    }

    public function getEntity($id = null): ?User
    {
        if (null === $id) {
            return new User();
        }

        $entity = parent::getEntity($id);

        if ($entity) {
            // add user's permissions
            $entity->setActivePermissions(
                $this->em->getRepository(\Mautic\UserBundle\Entity\Permission::class)->getPermissionsByRole($entity->getRole())
            );
        }

        return $entity;
    }

    /**
     * @return User|null
     */
    public function getSystemAdministrator()
    {
        $adminRole = $this->em->getRepository(Role::class)->findOneBy(['isAdmin' => true]);

        return $this->getRepository()->findOneBy(
            [
                'role'        => $adminRole,
                'isPublished' => true,
            ]
        );
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], $this->translator->trans('mautic.user.entity.must.be.user', [], 'validators'));
        }

        switch ($action) {
            case 'pre_save':
                $name = UserEvents::USER_PRE_SAVE;
                break;
            case 'post_save':
                $name = UserEvents::USER_POST_SAVE;
                break;
            case 'pre_delete':
                $name = UserEvents::USER_PRE_DELETE;
                break;
            case 'post_delete':
                $name = UserEvents::USER_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new UserEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = [];

        return match ($type) {
            'role'     => $this->em->getRepository(Role::class)->getRoleList($filter, $limit),
            'user'     => $this->em->getRepository(User::class)->getUserList($filter, $limit),
            'position' => $this->em->getRepository(User::class)->getPositionList($filter, $limit),
            default    => $results,
        };
    }

    /**
     * Resets the user password and emails it.
     *
     * @param string $newPassword
     */
    public function resetPassword(User $user, UserPasswordHasherInterface $hasher, $newPassword): void
    {
        $hashedPassword = $this->checkNewPassword($user, $hasher, $newPassword);

        $user->setPassword($hashedPassword);
        $this->saveEntity($user);
    }

    /**
     * @return UserToken
     */
    protected function getResetToken(User $user)
    {
        $userToken = new UserToken();
        $userToken->setUser($user)
            ->setAuthorizator(UserTokenAuthorizator::RESET_PASSWORD_AUTHORIZATOR)
            ->setExpiration((new \DateTime())->add(new \DateInterval('PT24H')))
            ->setOneTimeOnly();

        return $this->userTokenService->generateSecret($userToken, 64);
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function confirmResetToken(User $user, $token)
    {
        $userToken = new UserToken();
        $userToken->setUser($user)
            ->setAuthorizator(UserTokenAuthorizator::RESET_PASSWORD_AUTHORIZATOR)
            ->setSecret($token);

        return $this->userTokenService->verify($userToken);
    }

    /**
     * @throws PasswordResetTokenCreationFailedException
     */
    public function sendResetEmail(User $user): void
    {
        $mailer = $this->mailHelper->getMailer();

        $resetToken = $this->getResetToken($user);
        $this->em->persist($resetToken);
        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception $exception) {
            $this->logger->error($this->translator->trans('mautic.user.password.reset.token.creation.database.error', [], 'messages').': '.$exception->getMessage());
            throw new PasswordResetTokenCreationFailedException($this->translator->trans('mautic.user.password.reset.token.creation.failed'), 0, $exception);
        }
        $resetLink  = $this->router->generate('mautic_user_passwordresetconfirm', ['token' => $resetToken->getSecret()], UrlGeneratorInterface::ABSOLUTE_URL);

        $mailer->setTo([$user->getEmail() ?? '' => $user->getName()]);
        $mailer->setSubject($this->translator->trans('mautic.user.user.passwordreset.subject'));
        $text = $this->translator->trans(
            'mautic.user.user.passwordreset.email.body',
            ['%name%' => $user->getFirstName(), '%resetlink%' => '<a href="'.$resetLink.'">'.$resetLink.'</a>']
        );
        $text = str_replace('\\n', "\n", $text);
        $html = nl2br($text);

        $this->emailUser(
            $user,
            $this->translator->trans('mautic.user.user.passwordreset.subject'),
            $html
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function sendChangePasswordInfo(User $user): void
    {
        $text = $this->translator->trans(
            'mautic.user.user.passwordchange.email.body',
            ['%name%' => $user->getFirstName()]
        );
        $text = str_replace('\\n', "\n", $text);
        $html = nl2br($text);

        $this->emailUser(
            $user,
            $this->translator->trans('mautic.user.user.passwordchange.subject'),
            $html
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function sendChangeEmailInfo(string $oldEmail, User $user): void
    {
        $mailer = $this->mailHelper->getMailer();
        $text   = $this->translator->trans(
            'mautic.user.user.emailchange.email.body',
            ['%name%' => $user->getFirstName()]
        );
        $text = str_replace('\\n', "\n", $text);
        $html = nl2br($text);

        $mailer->setTo([$oldEmail => $user->getName()]);
        $mailer->setBody($html);
        $mailer->setSubject($this->translator->trans('mautic.user.user.emailchange.subject'));
        $mailer->send();
    }

    public function emailUser(User $user, string $subject, string $content): void
    {
        $mailer  = $this->prepareEMail($subject, $content);
        $mailer->setTo([$user->getEmail() ?? '' => $user->getName()]);
        $mailer->send();
    }

    /**
     * @param string[] $emailAddresses
     */
    public function sendMailToEmailAddresses(array $emailAddresses, string $subject, string $content): void
    {
        $mailer  = $this->prepareEMail($subject, $content);
        $mailer->setTo($emailAddresses);
        $mailer->send();
    }

    private function prepareEMail(string $subject, string $content): MailHelper
    {
        $mailer  = $this->mailHelper->getMailer();
        $content = str_replace('\\n', "\n", $content);
        $html    = nl2br($content);
        $mailer->setSubject($subject);
        $mailer->setBody($html);
        $mailer->setPlainText(strip_tags($content));

        return $mailer;
    }

    /**
     * Set user preference.
     */
    public function setPreference($key, $value = null, ?User $user = null): void
    {
        if (null == $user) {
            $user = $this->userHelper->getUser();
        }

        $preferences       = $user->getPreferences();
        $preferences[$key] = $value;

        $user->setPreferences($preferences);

        $this->getRepository()->saveEntity($user);
    }

    /**
     * Get user preference.
     */
    public function getPreference($key, $default = null, ?User $user = null)
    {
        if (null == $user) {
            $user = $this->userHelper->getUser();
        }
        $preferences = $user->getPreferences();

        return $preferences[$key] ?? $default;
    }

    /**
     * Return list of Users for formType Choice.
     */
    public function getOwnerListChoices(): array
    {
        return $this->getRepository()->getOwnerListChoices();
    }

    public function hasUserWithEmail(string $email): bool
    {
        return null !== $this->getRepository()->findOneBy(['email' => $email]);
    }

    public function createInvite(string $email, Role $role): UserInvite
    {
        $inviteToken = $this->createInviteToken();
        $invite      = (new UserInvite($role))
            ->setEmail($email)
            ->setTokenSelector($inviteToken['selector'])
            ->setTokenVerifierHash(password_hash($inviteToken['verifier'], PASSWORD_DEFAULT))
            ->setExpiration((new \DateTime())->add(new \DateInterval('PT48H')));
        $this->getUserInviteRepository()->revokeOutstandingInvites($email);
        $this->em->persist($invite);
        $this->em->flush();

        $link   = $this->router->generate('mautic_user_invite_register', ['token' => $inviteToken['token']], UrlGeneratorInterface::ABSOLUTE_URL);
        $mailer = $this->mailHelper->getMailer();
        $mailer->setTo([$email => $email]);
        $mailer->setSubject($this->translator->trans('mautic.user.invite.subject'));
        $text = $this->translator->trans('mautic.user.invite.email.body', ['%invite_link%' => $link]);
        $text = str_replace('\\n', "\n", $text);
        $mailer->setBody($this->twig->render('@MauticUser/Email/invite.html.twig', [
            'inviteLink' => $link,
        ]));
        $mailer->setPlainText($text);
        $mailer->send();

        return $invite;
    }

    public function getInvite(string $token): ?UserInvite
    {
        $inviteToken = $this->parseInviteToken($token);
        if (null === $inviteToken) {
            $this->logInvalidInvite('token format is invalid');

            return null;
        }

        $invite = $this->getUserInviteRepository()->findOneByTokenSelector($inviteToken['selector']);
        if (null === $invite) {
            $this->logInvalidInvite('token selector was not found', null, $inviteToken['selector']);

            return null;
        }
        if ($invite->isUsed()) {
            $this->logInvalidInvite('invite has already been used', $invite);

            return null;
        }
        if ($invite->getExpiration() < new \DateTime()) {
            $this->logInvalidInvite('invite has expired', $invite);

            return null;
        }
        if (!password_verify($inviteToken['verifier'], (string) $invite->getTokenVerifierHash())) {
            $this->logInvalidInvite('token verifier did not match', $invite);

            return null;
        }

        return $invite;
    }

    public function markInviteUsed(UserInvite $invite): void
    {
        $invite->setUsed(true);
        $this->em->persist($invite);
        $this->em->flush();
    }

    /**
     * @return array{selector: string, verifier: string, token: string}
     */
    private function createInviteToken(): array
    {
        $selector = bin2hex(random_bytes(self::INVITE_TOKEN_SELECTOR_BYTES));
        $verifier = bin2hex(random_bytes(self::INVITE_TOKEN_VERIFIER_BYTES));

        return [
            'selector' => $selector,
            'verifier' => $verifier,
            'token'    => $selector.'.'.$verifier,
        ];
    }

    /**
     * @return array{selector: string, verifier: string}|null
     */
    private function parseInviteToken(string $token): ?array
    {
        $tokenParts = explode('.', $token, 2);
        if (2 !== count($tokenParts)) {
            return null;
        }

        [$selector, $verifier] = $tokenParts;
        if ('' === $selector || '' === $verifier) {
            return null;
        }

        return [
            'selector' => $selector,
            'verifier' => $verifier,
        ];
    }

    private function getUserInviteRepository(): UserInviteRepositoryInterface
    {
        $repository = $this->em->getRepository(UserInvite::class);
        \assert($repository instanceof UserInviteRepositoryInterface);

        return $repository;
    }

    private function logInvalidInvite(string $reason, ?UserInvite $invite = null, ?string $selector = null): void
    {
        if ($invite) {
            $context = [
                'invite_id' => $invite->getId(),
                'email'     => $invite->getEmail(),
            ];
        } elseif (null !== $selector) {
            $context = ['selector' => $selector];
        } else {
            $context = [];
        }

        $this->logger->warning('User invite link rejected: '.$reason, $context);
    }
}
