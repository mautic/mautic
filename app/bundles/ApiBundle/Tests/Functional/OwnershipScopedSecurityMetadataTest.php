<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

/**
 * Contract test: every ownership-scoped item operation in every API v2 resource
 * must pass `object` to its security expression.
 *
 * Without `object`, ApiPermissionVoter cannot check entity ownership and will
 * grant access to any user holding the `own` permission bit — regardless of
 * whether they actually own that specific entity.
 */
final class OwnershipScopedSecurityMetadataTest extends MauticMysqlTestCase
{
    public function testAllOwnershipScopedItemOperationsPassObjectToSecurityExpression(): void
    {
        // IpAddress is a pure audit log with no owner or parent entity,
        // so ownership-scoped security expressions on it remain permission-bit-only.
        $knownNoOwnerClasses = [
            \Mautic\CoreBundle\Entity\IpAddress::class,
        ];

        /** @var ResourceNameCollectionFactoryInterface $nameFactory */
        $nameFactory = self::getContainer()->get('api_platform.metadata.resource.name_collection_factory');

        /** @var ResourceMetadataCollectionFactoryInterface $metadataFactory */
        $metadataFactory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');

        $violations = [];

        foreach ($nameFactory->create() as $resourceClass) {
            if (in_array($resourceClass, $knownNoOwnerClasses, true)) {
                continue;
            }

            foreach ($metadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if (!$operation instanceof HttpOperation) {
                        continue;
                    }

                    // Collection and create operations do not load a single entity — skip.
                    if ($operation instanceof GetCollection || $operation instanceof Post) {
                        continue;
                    }

                    $security = $operation->getSecurity();

                    if (null === $security) {
                        continue;
                    }

                    // Only check expressions that use ownership-scoped permission levels.
                    if (!preg_match('/is_granted\(\'[^\']+:(?:view|edit|delete)(?:own|other)\'/', $security)) {
                        continue;
                    }

                    if (!str_contains($security, ', object)')) {
                        $violations[] = sprintf(
                            '  %s [%s]: %s',
                            $resourceClass,
                            $operationName,
                            $security
                        );
                    }
                }
            }
        }

        self::assertEmpty(
            $violations,
            "The following ownership-scoped item operations are missing ', object)' in their security expression:\n"
            .implode("\n", $violations)
        );
    }
}
