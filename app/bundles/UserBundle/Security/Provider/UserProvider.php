<?php

namespace Mautic\UserBundle\Security\Provider;

use Mautic\CoreBundle\Cache\ResultCacheHelper;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        protected UserRepository $userRepository,
        protected PermissionRepository $permissionRepository,
        protected EventDispatcherInterface $dispatcher,
        protected UserPasswordHasher $encoder,
    ) {
    }

    /**
     * @param string $username
     */
    public function loadUserByUsername($username): User
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $qb = $this->userRepository
            ->createQueryBuilder('u')
            ->select('u, r')
            ->leftJoin('u.role', 'r')
            ->where('u.username = :username OR u.email = :username')
            ->andWhere('u.isPublished = :true')
            ->setParameter('true', true, 'boolean')
            ->setParameter('username', $identifier);
        $query = $qb->getQuery();
        ResultCacheHelper::enableOrmQueryCache($query, new ResultCacheOptions(User::CACHE_NAMESPACE, 5 * 60));
        $user = $query->getOneOrNullResult();

        if (empty($user)) {
            $message = sprintf(
                'Unable to find an active admin MauticUserBundle:User object identified by "%s".',
                $identifier
            );
            throw new UserNotFoundException($message, 0);
        }

        // load permissions
        if ($user->getId()) {
            $permissions = $this->permissionRepository->getPermissionsByRole($user->getRole());
            $user->setActivePermissions($permissions);
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = $user::class;
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Create/update user from authentication plugins.
     *
     * @param bool|true $createIfNotExists
     *
     * @return User
     *
     * @throws BadCredentialsException
     */
    public function saveUser(User $user, $createIfNotExists = true)
    {
        $isNew = !$user->getId();

        if ($isNew) {
            $user = $this->findUser($user);
            if (!$user->getId() && !$createIfNotExists) {
                throw new BadCredentialsException();
            }
        }

        // Validation for User objects returned by a plugin
        if (!$user->getRole()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_role');
        }

        if (!$user->getUserIdentifier()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_username');
        }

        if (!$user->getEmail()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_email');
        }

        if (!$user->getFirstName() || !$user->getLastName()) {
            throw new AuthenticationException('mautic.integration.sso.error.no_name');
        }

        // Check for plain password
        $plainPassword = $user->getPlainPassword();
        if ($plainPassword) {
            // Encode plain text
            $user->setPassword(
                $this->encoder->hashPassword($user, $plainPassword)
            );
        } elseif (!$password = $user->getPassword()) {
            // Generate and encode a random password
            $user->setPassword(
                $this->encoder->hashPassword($user, EncryptionHelper::generateKey())
            );
        }

        $event = new UserEvent($user, $isNew);

        if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_SAVE)) {
            $event = $this->dispatcher->dispatch($event, UserEvents::USER_PRE_SAVE);
        }

        $this->userRepository->saveEntity($user);

        if ($this->dispatcher->hasListeners(UserEvents::USER_POST_SAVE)) {
            $this->dispatcher->dispatch($event, UserEvents::USER_POST_SAVE);
        }

        return $user;
    }

    /**
     * @return User
     */
    public function findUser(User $user)
    {
        try {
            // Try by username
            $user = $this->loadUserByIdentifier($user->getUserIdentifier());

            return $user;
        } catch (UserNotFoundException) {
            // Try by email
            try {
                return $this->loadUserByIdentifier($user->getEmail());
            } catch (UserNotFoundException) {
            }
        }

        return $user;
    }
}
