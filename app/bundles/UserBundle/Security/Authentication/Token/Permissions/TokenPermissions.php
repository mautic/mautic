<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authentication\Token\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Model\TokenInterface as OAuthTokenInterface;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Mautic\ApiBundle\Entity\oAuth2\AccessToken;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenPermissions
{
    public function __construct(private TokenStorageInterface $tokenStorage, private PermissionRepository $permissionRepository, private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Set the active permissions on the current user.
     */
    public function setActivePermissionsOnAuthToken(TokenInterface|OAuthTokenInterface|null $token = null): ?UserInterface
    {
        if (null === $token) {
            $token = $this->tokenStorage->getToken();
        }

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        \assert(null === $user || $user instanceof User);

        // If no user is associated with a token, it's a client credentials grant type. Handle accordingly.
        if (null === $user && ($token instanceof OAuthToken || $token instanceof OAuthTokenInterface)) {
            $user = $this->assignRoleFromToken($token->getToken());
        }

        if (null !== $user) {
            $this->setPermissionsOnUser($user);
        }

        if (null === $user) {
            throw new \RuntimeException('The user should be either already set in the token, or come from assignRoleFromToken.');
        }

        $token->setUser($user);

        if ($token instanceof TokenInterface) {
            $this->tokenStorage->setToken($token);
        }

        return $user;
    }

    /**
     * Handle permission for Client Credential grant type.
     */
    private function assignRoleFromToken(string $tokenIdentifier): User
    {
        /** @var AccessToken|null $accessToken assert ill yield phpstan error. */
        $accessToken = $this->entityManager->getRepository(AccessToken::class)->findOneBy(['token' => $tokenIdentifier]);

        if (null === $accessToken) {
            throw new UserNotFoundException('API access token not found.');
        }

        $client = $accessToken->getClient();
        if (!$client instanceof Client) {
            // There are no tests for this part, so an exception will reveal any inconsistencies earlier.
            throw new \RuntimeException('The client is not a valid API client.');
        }

        $role = $client->getRole();

        // Create a pseudo user and assign the role
        $user = new User();
        $user->setRole($role);

        // Set for the audit log and the entity's "created by user" metadata which takes the first and last name
        $user->setFirstName($client->getName());
        $user->setLastName(sprintf('[%s]', $client->getId()));
        $user->setUsername($user->getName());
        defined('MAUTIC_AUDITLOG_USER') || define('MAUTIC_AUDITLOG_USER', $user->getName());

        return $user;
    }

    private function setPermissionsOnUser(User $user): void
    {
        if (!$user->isAdmin() && (null === $user->getActivePermissions() || [] === $user->getActivePermissions())) {
            $activePermissions = $this->permissionRepository->getPermissionsByRole($user->getRole());

            $user->setActivePermissions($activePermissions);
        }
    }
}
