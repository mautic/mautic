<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\User;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserFactoryInterface::class)]
final class UserFactory implements UserFactoryInterface
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private Settings $parameters;
    private LoggerInterface $logger;

    public function __construct(
        Settings $parameters,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        LoggerInterface $logger,
    ) {
        $this->parameters     = $parameters;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->logger         = $logger;
    }

    public function create(UserCredentials $credentials): User
    {
        if (!$this->parameters->isUserRegistrationAllowed()) {
            $this->logger->debug('User registration is not allowed');
            throw new OidcException('mautic.open_id.registration.exception.registration_disabled');
        }

        $role = $this->getUserRole();
        if (!$role) {
            $this->logger->error('Could not register user through OpenID Connect. Registered user role not found.', ['role' => $this->parameters->getRegisteredUserRole()]);
            throw new OidcException('mautic.open_id.registration.exception.invalid_role');
        }

        if (!$credentials->getEmail()) {
            $this->logger->error('Could not locate email to register user through Open ID Connect.', ['credentials' => $credentials]);
            throw new OidcException('mautic.open_id.registration.exception.email_required');
        }

        if ($this->userRepository->findOneBy(['email' => $credentials->getEmail()])) {
            $this->logger->debug('Could not register user through Open ID Connect. Email is already taken.', ['credentials' => $credentials]);
            throw new OidcException('mautic.open_id.registration.exception.email_taken');
        }

        $username    = $this->getUniqueUsername($credentials);
        $createdUser = new User();
        $createdUser->setRole($role);
        $createdUser->setUsername($username);
        $createdUser->setEmail($credentials->getEmail());
        $createdUser->setFirstName($credentials->getGivenName() ?? $username);
        $createdUser->setLastName($credentials->getFamilyName() ?? $username);
        $createdUser->setIsPublished(true);

        return $createdUser;
    }

    private function getUniqueUsername(UserCredentials $credentials): string
    {
        $username = $credentials->getPreferredUsername();
        if (!$username || $this->userRepository->findOneBy(['username' => $username])) {
            $username = $credentials->getEmail();
        }

        return $username;
    }

    private function getUserRole(): ?Role
    {
        $role = $this->parameters->getRegisteredUserRole();
        if (!$role) {
            return null;
        }

        return $this->roleRepository->find($role->getId());
    }
}
