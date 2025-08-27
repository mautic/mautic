<?php

namespace Mautic\ApiBundle\Security\Voter;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiPermissionVoter extends Voter
{
    public function __construct(private CorePermissions $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support Mautic permission format like 'focus:items:viewown'
        return str_contains($attribute, ':');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        // Use Mautic's security system to check permissions
        return $this->security->isGranted($attribute);
    }
}
