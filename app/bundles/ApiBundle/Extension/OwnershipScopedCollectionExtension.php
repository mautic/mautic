<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Applies ownership-scoped filtering for API v2 collections using the same
 * own/other semantics as hasEntityAccess() item checks.
 *
 * Filtering rules:
 * - own and not other: only owned items
 * - other and not own: only foreign (plus unowned) items
 * - own and other: no filtering
 */
final class OwnershipScopedCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (null === $operation) {
            return;
        }

        $securityExpression = $operation->getSecurity();
        if (null === $securityExpression || !is_string($securityExpression)) {
            return;
        }

        $ownPermission = $this->getOwnPermissionFromExpression($securityExpression);
        if (null === $ownPermission) {
            return;
        }

        $otherPermission = $this->getOtherPermissionFromOwnPermission($ownPermission);
        $canViewOwn      = $this->security->isGranted($ownPermission);
        $canViewOther    = $this->security->isGranted($otherPermission);

        // If user has both permissions or neither, no filtering is needed
        if (($canViewOwn && $canViewOther) || (!$canViewOwn && !$canViewOther)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof UserInterface || !method_exists($user, 'getId')) {
            return;
        }

        $userId = $user->getId();
        if (null === $userId) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0] ?? null;
        if (null === $rootAlias) {
            return;
        }

        // Determine how to filter based on ownership
        $ownershipInfo = $this->getOwnershipFilterField($resourceClass, $rootAlias, $queryBuilder);
        if (null === $ownershipInfo) {
            // Cannot determine ownership for this entity, skip filtering
            return;
        }

        [$ownershipField, $usesOwnerField] = $ownershipInfo;

        $parameterName = $queryNameGenerator->generateParameterName('created_by');

        if ($canViewOwn && !$canViewOther) {
            if ($usesOwnerField) {
                // Lead/Company pattern: owner = userId OR (owner IS NULL AND createdBy = userId)
                // Extract the base alias from the ownership field (e.g., 'o.owner' -> 'o')
                $parts     = explode('.', $ownershipField);
                $baseAlias = $parts[0];

                $queryBuilder
                    ->andWhere(
                        sprintf(
                            '(%s.owner = :%s OR (%s.owner IS NULL AND %s.createdBy = :%s))',
                            $baseAlias,
                            $parameterName,
                            $baseAlias,
                            $baseAlias,
                            $parameterName
                        )
                    )
                    ->setParameter($parameterName, $userId);
            } else {
                $queryBuilder
                    ->andWhere(sprintf('%s = :%s', $ownershipField, $parameterName))
                    ->setParameter($parameterName, $userId);
            }

            return;
        }

        if ($usesOwnerField) {
            // Lead/Company pattern: NOT owned by current user
            $parts     = explode('.', $ownershipField);
            $baseAlias = $parts[0];

            $queryBuilder
                ->andWhere(
                    sprintf(
                        '((%s.owner != :%s OR %s.owner IS NULL) AND (%s.owner IS NOT NULL OR %s.createdBy != :%s OR %s.createdBy IS NULL))',
                        $baseAlias,
                        $parameterName,
                        $baseAlias,
                        $baseAlias,
                        $baseAlias,
                        $parameterName,
                        $baseAlias
                    )
                )
                ->setParameter($parameterName, $userId);
        } else {
            // Match hasEntityAccess() semantics for "other": include all
            // entities not created by the current user, plus unowned entities.
            $queryBuilder
                ->andWhere(
                    sprintf('(%s != :%s OR %s IS NULL)', $ownershipField, $parameterName, $ownershipField)
                )
                ->setParameter($parameterName, $userId);
        }
    }

    /**
     * Determines the ownership filter field for the given resource.
     *
     * Strategy:
     * 1. Check if entity has direct owner field (like Lead/Company)
     * 2. Otherwise check for direct createdBy field
     * 3. For child entities with getPermissionUser(), check for association marked with isOwnershipParent
     * 4. Otherwise skip filtering (no ownership field defined)
     *
     * Child entities that delegate permission checks should mark the parent association:
     *   $builder->createManyToOne('parent', 'ParentEntity')->isOwnershipParent()->build();
     *
     * @return array{0: string, 1: bool}|null Returns [field path, uses owner field] or null if not applicable
     */
    private function getOwnershipFilterField(string $resourceClass, string $rootAlias, QueryBuilder $queryBuilder): ?array
    {
        /** @phpstan-var class-string $resourceClass */
        $metadata = $this->entityManager->getClassMetadata($resourceClass);

        // Check if entity has owner field (mirrors getOwner() ?? getCreatedBy() pattern)
        // Used by Lead/Company: getPermissionUser() returns getOwner() ?? getCreatedBy()
        if ($metadata->hasField('owner') || $metadata->hasAssociation('owner')) {
            return [sprintf('%s.owner', $rootAlias), true];
        }

        // Check if entity has direct createdBy field
        if ($metadata->hasField('createdBy') || $metadata->hasAssociation('createdBy')) {
            return [sprintf('%s.createdBy', $rootAlias), false];
        }

        // For entities without direct ownership field, check if any association is marked as ownership parent
        // Child entities mark this in their metadata with ->isOwnershipParent() in loadMetadata()
        foreach ($metadata->getAssociationNames() as $associationName) {
            $associationMapping = $metadata->getAssociationMapping($associationName);
            if (isset($associationMapping['isOwnershipParent']) && $associationMapping['isOwnershipParent']) {
                $targetClass    = $metadata->getAssociationTargetClass($associationName);
                /** @phpstan-var class-string $targetClass */
                $targetMetadata = $this->entityManager->getClassMetadata($targetClass);

                // Check parent's ownership field (prefer owner over createdBy)
                if ($targetMetadata->hasField('owner') || $targetMetadata->hasAssociation('owner')) {
                    $parentAlias = $associationName.'_ownership';
                    $queryBuilder->leftJoin(sprintf('%s.%s', $rootAlias, $associationName), $parentAlias);

                    return [sprintf('%s.owner', $parentAlias), true];
                }

                if ($targetMetadata->hasField('createdBy') || $targetMetadata->hasAssociation('createdBy')) {
                    $parentAlias = $associationName.'_ownership';
                    $queryBuilder->leftJoin(sprintf('%s.%s', $rootAlias, $associationName), $parentAlias);

                    return [sprintf('%s.createdBy', $parentAlias), false];
                }
            }
        }

        // Cannot determine ownership automatically - skip filtering for safety
        return null;
    }

    /**
     * Extract an ":own" permission from the operation security expression.
     */
    private function getOwnPermissionFromExpression(string $securityExpression): ?string
    {
        // Match a permission string ending with "own" inside is_granted()
        if (preg_match("/is_granted\\('([^']+own)'/", $securityExpression, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getOtherPermissionFromOwnPermission(string $ownPermission): string
    {
        return substr($ownPermission, 0, -3).'other';
    }
}
