<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\User;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserFactoryInterface::class)]
final readonly class UserFactory implements UserFactoryInterface
{
    public function __construct(private Settings $parameters, private UserRepository $userRepository, private RoleRepository $roleRepository, private LoggerInterface $logger)
    {
    }

    public function create(UserCredentials $credentials): User
    {
        if (!$this->parameters->isUserRegistrationAllowed()) {
            $this->logger->debug('User registration is not allowed');
            throw new OidcException('mautic.open_id.registration.exception.registration_disabled');
        }

        $role = $this->getUserRole();
        if (!$role) {
            $this->logger->error('Could not register user through OpenID Connect. Registered user role not found.', ['roleId' => $this->parameters->getRegisteredUserRoleId()]);
            throw new OidcException('mautic.open_id.registration.exception.invalid_role');
        }

        if (!$credentials->getEmail()) {
            $this->logger->error('Could not locate email to register user through Open ID Connect.');
            throw new OidcException('mautic.open_id.registration.exception.email_required');
        }

        if (null !== $this->userRepository->findByIdentifier($credentials->getEmail())) {
            $this->logger->debug('Could not register user through Open ID Connect. Identity is already taken.');
            throw new OidcException('mautic.open_id.registration.exception.identity_taken');
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

        // Prefer preferred_username only when it does not collide case-insensitively
        // with an existing username or email; otherwise fall back to the email.
        if (!$username || null !== $this->userRepository->findByIdentifier($username)) {
            $username = $credentials->getEmail();
        }

        return $username;
    }

    private function getUserRole(): ?Role
    {
        $roleId = $this->parameters->getRegisteredUserRoleId();
        if (!$roleId) {
            return null;
        }

        return $this->roleRepository->find($roleId);
    }
}
