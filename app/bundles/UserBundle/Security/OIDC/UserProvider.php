<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\UserFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Service\LinkerInterface;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

// this wraps the Mautic UserProvider to provide a UserInterface for the OpenIdBundle without having to duplicate the code
final class UserProvider implements CredentialsUserProviderInterface
{
    private LinkerInterface $linker;
    private UserFactoryInterface $userFactory;
    private MauticUserProvider $userProvider;
    private Security $security;

    public function __construct(
        LinkerInterface $linker,
        UserFactoryInterface $userFactory,
        MauticUserProvider $userProvider,
        Security $security,
    ) {
        $this->linker       = $linker;
        $this->userFactory  = $userFactory;
        $this->userProvider = $userProvider;
        $this->security     = $security;
    }

    public function loadUserByUsername($username): UserInterface
    {
        $campaignStudioUser = $this->security->getUser();
        \assert(null === $campaignStudioUser || $campaignStudioUser instanceof User);

        if ($user = $this->linker->findLinkedUser($username, $campaignStudioUser)) {
            return $this->userProvider->loadUserByUsername($user->getUsername());
        }

        throw new UsernameNotFoundException();
    }

    public function loadUserByCredentials(UserCredentials $credentials): UserInterface
    {
        $openIdConnectId    = $credentials->getId();
        $campaignStudioUser = $this->security->getUser();
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
