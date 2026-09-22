<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Mautic\UserBundle\Security\OIDC\User\UserFactoryInterface;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
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

        if ($linkedUser = $this->linker->findLinkedUser($openIdConnectId, $currentUser)) {
            return $this->userProvider->loadUserByIdentifier($linkedUser->getUsername());
        }

        if ($currentUser) {
            $linkedUser = $this->linker->linkToUser($openIdConnectId, $currentUser);

            return $this->userProvider->loadUserByIdentifier($linkedUser->getUsername());
        }

        $newUser = $this->userProvider->saveUser($this->userFactory->create($credentials));
        $linkedUser = $this->linker->linkToUser($credentials->getId(), $newUser);

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
