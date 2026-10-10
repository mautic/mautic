<?php

namespace Mautic\ApiBundle\Security\Voter;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiPlatformPermissionContextEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiPermissionVoter extends Voter
{
    public function __construct(
        private readonly CorePermissions $security,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support Mautic permission format like 'focus:items:viewown'
        return str_contains($attribute, ':');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $permissionContextEvent = new ApiPlatformPermissionContextEvent(
            $attribute,
            $attribute,
            $subject,
        );
        $this->dispatcher->dispatch($permissionContextEvent, ApiEvents::API_PLATFORM_PERMISSION_CONTEXT);

        $attribute = $permissionContextEvent->getPermission();
        $subject   = $permissionContextEvent->getRequestObject();

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if (is_object($subject) && $this->isOwnershipScopedPermission($attribute)) {
            $ownerId         = $this->resolveOwner($subject) ?? 0;
            $ownPermission   = $this->isOwnScopePermission($attribute) ? $attribute : $this->toOwnScopePermission($attribute);
            $otherPermission = $this->isOwnScopePermission($attribute) ? $this->toOtherScopePermission($attribute) : $attribute;

            return $this->security->hasEntityAccess(
                $ownPermission,
                $otherPermission,
                $ownerId
            );
        }

        // Fallback to permission bit checks for non-owner scoped permissions.
        return $this->security->isGranted($attribute);
    }

    private function isOwnScopePermission(string $attribute): bool
    {
        $parts = explode(':', $attribute);
        $level = end($parts);

        return is_string($level) && str_ends_with($level, 'own');
    }

    private function isOtherScopePermission(string $attribute): bool
    {
        $parts = explode(':', $attribute);
        $level = end($parts);

        return is_string($level) && str_ends_with($level, 'other');
    }

    private function isOwnershipScopedPermission(string $attribute): bool
    {
        return $this->isOwnScopePermission($attribute) || $this->isOtherScopePermission($attribute);
    }

    private function toOtherScopePermission(string $attribute): string
    {
        return substr($attribute, 0, -3).'other';
    }

    private function toOwnScopePermission(string $attribute): string
    {
        return substr($attribute, 0, -5).'own';
    }

    private function resolveOwner(mixed $subject): mixed
    {
        if (!is_object($subject)) {
            return null;
        }

        // Try getPermissionUser() first (for child entities like Form\Action, Form\Field, etc.)
        if (method_exists($subject, 'getPermissionUser')) {
            return $subject->getPermissionUser();
        }

        // Fallback to getCreatedBy() (most common case)
        if (method_exists($subject, 'getCreatedBy')) {
            return $subject->getCreatedBy();
        }

        // Final fallback to getOwner()
        if (method_exists($subject, 'getOwner')) {
            return $subject->getOwner();
        }

        return null;
    }
}
