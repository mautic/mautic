<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\ApiPlatform\EventListener;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiPlatformPermissionContextEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class MauticDenyAccessListener
{
    public function __construct(
        private CorePermissions $security,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function onSecurity(RequestEvent $event): void
    {
        $this->checkSecurity($event->getRequest());
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function checkSecurity(Request $request): void
    {
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata   = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $operation          = $resourceMetadata->getOperation($attributes['operation_name']);
        $securityExpression = $operation->getSecurity() ?? null;
        $permission         = is_string($securityExpression) ? $this->extractPermission($securityExpression) : null;

        if (null === $permission) {
            return;
        }

        $requestObject = $request->attributes->get('data');
        $permissionContextEvent = new ApiPlatformPermissionContextEvent(
            $securityExpression,
            $permission,
            $requestObject,
            $request,
            $attributes,
        );
        $this->dispatcher->dispatch($permissionContextEvent, ApiEvents::API_PLATFORM_PERMISSION_CONTEXT);

        $permission    = $permissionContextEvent->getPermission();
        $requestObject = $permissionContextEvent->getRequestObject();

        if ($this->shouldCheckEntityOwnership($permission)) {
            [$ownPermission, $otherPermission] = $this->resolveOwnershipPermissions($permission);

            if (!$this->security->hasEntityAccess($ownPermission, $otherPermission, $this->resolveOwner($requestObject))) {
                throw new AccessDeniedException();
            }

            return;
        }

        if (!$this->security->isGranted($permission)) {
            throw new AccessDeniedException();
        }
    }

    private function shouldCheckEntityOwnership(string $permission): bool
    {
        if (preg_match('/:(?:view|edit|delete)(?:own|other)$/', $permission)) {
            return true;
        }

        return (bool) preg_match('/:(?:view|edit|delete)$/', $permission);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOwnershipPermissions(string $permission): array
    {
        if (preg_match('/(?:own|other)$/', $permission)) {
            $basePermission = preg_replace('/(?:own|other)$/', '', $permission) ?? $permission;

            return [$basePermission.'own', $basePermission.'other'];
        }

        return [$permission.'own', $permission.'other'];
    }

    private function extractPermission(string $securityExpression): ?string
    {
        if (preg_match("/is_granted\\('([^']+)'/", $securityExpression, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^["\']([^"\']+)["\']$/', trim($securityExpression), $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function resolveOwner(mixed $requestObject): mixed
    {
        $owner = 0;

        if (is_object($requestObject)) {
            if (method_exists($requestObject, 'getPermissionUser')) {
                $owner = $requestObject->getPermissionUser() ?? 0;
            } elseif (method_exists($requestObject, 'getCreatedBy')) {
                $owner = $requestObject->getCreatedBy() ?? 0;
            } elseif (method_exists($requestObject, 'getOwner')) {
                $owner = $requestObject->getOwner() ?? 0;
            }
        }

        return $owner;
    }
}
