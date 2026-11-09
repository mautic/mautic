<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Mautic\UserBundle\Security\OIDC\User\UserFactoryInterface;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

// this wraps the Mautic UserProvider to provide a UserInterface for the OpenIdBundle without having to duplicate the code
final readonly class UserProvider implements CredentialsUserProviderInterface
{
    public function __construct(
        private LinkerInterface $linker,
        private UserFactoryInterface $userFactory,
        private MauticUserProvider $userProvider,
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        \assert(null === $user || $user instanceof User);

        if ($user = $this->linker->findLinkedUser($identifier, $user)) {
            return $this->userProvider->loadUserByIdentifier($user->getUsername());
        }

        throw new UserNotFoundException();
    }

    public function loadUserByCredentials(UserCredentials $credentials): UserInterface
    {
        $openIdConnectId = $credentials->getId();
        $token = $this->tokenStorage->getToken();
        $currentUser = $token?->getUser();
        \assert(null === $currentUser || $currentUser instanceof User);

        if ($linkedUser = $this->linker->findLinkedUser($openIdConnectId, $currentUser)) {
            return $this->userProvider->loadUserByIdentifier($linkedUser->getUsername());
        }

        if ($currentUser) {
            $linkedUser = $this->linker->linkToUser($openIdConnectId, $currentUser);

            return $this->userProvider->loadUserByIdentifier($linkedUser->getUsername());
        }

        $createdUser = $this->userFactory->create($credentials);

        // Bypass saveUser/findUser to prevent account takeover via username/email collision
        // saveUser's findUser() matches "username OR email", which an attacker can exploit
        // Instead, persist the user directly without the findUser logic

        // Generate a random password for OIDC-created users
        $createdUser->setPassword(
            $this->passwordHasher->hashPassword($createdUser, bin2hex(random_bytes(32)))
        );

        // Dispatch USER_PRE_SAVE event (for audit log, etc.)
        $event = new UserEvent($createdUser, true);
        if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_SAVE)) {
            $event = $this->dispatcher->dispatch($event, UserEvents::USER_PRE_SAVE);
        }

        $this->entityManager->persist($createdUser);
        $this->entityManager->flush();

        // Dispatch USER_POST_SAVE event
        if ($this->dispatcher->hasListeners(UserEvents::USER_POST_SAVE)) {
            $this->dispatcher->dispatch($event, UserEvents::USER_POST_SAVE);
        }

        $linkedUser = $this->linker->linkToUser($credentials->getId(), $createdUser);

        return $this->userProvider->loadUserByIdentifier($linkedUser->getUsername());
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->userProvider->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return $this->userProvider->supportsClass($class);
    }
}
