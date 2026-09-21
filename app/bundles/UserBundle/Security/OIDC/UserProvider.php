<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\UserFactoryInterface;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

// this wraps the Mautic UserProvider to provide a UserInterface for the OpenIdBundle without having to duplicate the code
final class UserProvider implements CredentialsUserProviderInterface
{
    public function __construct(
        private readonly LinkerInterface $linker,
        private readonly UserFactoryInterface $userFactory,
        private readonly MauticUserProvider $userProvider,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function loadUserByUsername($username): UserInterface
    {
        $token = $this->tokenStorage->getToken();
        $campaignStudioUser = $token?->getUser();
        \assert(null === $campaignStudioUser || $campaignStudioUser instanceof User);

        if ($user = $this->linker->findLinkedUser($username, $campaignStudioUser)) {
            return $this->userProvider->loadUserByUsername($user->getUsername());
        }

        throw new UsernameNotFoundException();
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    public function loadUserByCredentials(UserCredentials $credentials): UserInterface
    {
        $openIdConnectId    = $credentials->getId();
        $token = $this->tokenStorage->getToken();
        $campaignStudioUser = $token?->getUser();
        \assert(null === $campaignStudioUser || $campaignStudioUser instanceof User);

        if ($user = $this->linker->findLinkedUser($openIdConnectId, $campaignStudioUser)) {
            return $this->userProvider->loadUserByUsername($user->getUsername());
        }

        if ($campaignStudioUser) {
            \assert($campaignStudioUser instanceof User);
            $user = $this->linker->linkToUser($openIdConnectId, $campaignStudioUser);

            return $this->userProvider->loadUserByUsername($user->getUsername());
        }

        $user = $this->userProvider->saveUser($this->userFactory->create($credentials));
        $user = $this->linker->linkToUser($credentials->getId(), $user);

        return $this->userProvider->loadUserByUsername($user->getUsername());
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->userProvider->refreshUser($user);
    }

    public function supportsClass($class): bool
    {
        return $this->userProvider->supportsClass($class);
    }
}
