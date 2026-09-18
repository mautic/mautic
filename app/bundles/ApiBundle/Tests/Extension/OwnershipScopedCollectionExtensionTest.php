<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\ApiBundle\Extension\OwnershipScopedCollectionExtension;
use Mautic\ApiBundle\Tests\Extension\Fixture\OwnershipParentMissingAssociation;
use Mautic\ApiBundle\Tests\Extension\Fixture\OwnershipParentWithoutOwnership;
use Mautic\ApiBundle\Tests\Extension\Fixture\UnownedParent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

#[AllowMockObjectsWithoutExpectations]
final class OwnershipScopedCollectionExtensionTest extends TestCase
{
    private Security&MockObject $security;

    private EntityManagerInterface&MockObject $entityManager;

    private OwnershipScopedCollectionExtension $extension;

    protected function setUp(): void
    {
        $this->security      = $this->createMock(Security::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->extension     = new OwnershipScopedCollectionExtension($this->security, $this->entityManager);
    }

    public function testNoFilterAppliedWhenUserHasBothOwnAndOtherPermission(): void
    {
        $this->security->method('isGranted')->willReturn(true);

        // No need to stub user/metadata - should return early when both permissions granted
        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
            new GetCollection(security: "is_granted('lead:leads:viewown')"),
        );
    }

    public function testNoFilterAppliedWhenUserHasNeitherPermission(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        // No need to stub user/metadata - should return early when neither permission granted
        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
            new GetCollection(security: "is_granted('lead:leads:viewown')"),
        );
    }

    public function testOwnFilterAppliedWhenUserHasOnlyOwnPermission(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $p): bool => 'lead:leads:viewown' === $p);

        $user = $this->createUserWithId(42);
        $this->security->method('getUser')->willReturn($user);

        // Mock entity metadata with createdBy field
        $this->mockEntityMetadataWithCreatedBy(\stdClass::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['o']);
        $queryBuilder->method('expr')->willReturn(new Expr());
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('o.createdBy = :generated_created_by')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('generated_created_by', 42)
            ->willReturnSelf();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator('generated_created_by'),
            \stdClass::class,
            new GetCollection(security: "is_granted('lead:leads:viewown')"),
        );
    }

    public function testOtherFilterAppliedWhenUserHasOnlyOtherPermission(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $p): bool => 'lead:leads:viewother' === $p);

        $user = $this->createUserWithId(7);
        $this->security->method('getUser')->willReturn($user);

        // Mock entity metadata with createdBy field
        $this->mockEntityMetadataWithCreatedBy(\stdClass::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['o']);
        $queryBuilder->method('expr')->willReturn(new Expr());
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('(o.createdBy != :generated_created_by OR o.createdBy IS NULL)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('generated_created_by', 7)
            ->willReturnSelf();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator('generated_created_by'),
            \stdClass::class,
            new GetCollection(security: "is_granted('lead:leads:viewown')"),
        );
    }

    public function testNoFilterAppliedWhenOperationHasNoSecurityExpression(): void
    {
        $this->security->expects($this->never())->method('isGranted');

        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
            new GetCollection(),
        );
    }

    public function testNoFilterAppliedWhenSecurityExpressionHasNoOwnPermission(): void
    {
        $this->security->expects($this->never())->method('isGranted');

        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
            new GetCollection(security: "is_granted('api:access:full')"),
        );
    }

    public function testNoFilterAppliedWhenOperationIsNull(): void
    {
        $this->security->expects($this->never())->method('isGranted');

        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
        );
    }

    public function testOtherPermissionDerivationDoesNotCorruptOwnInMiddleOfString(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $p): bool => 'company:ownleads:viewown' === $p);

        $user = $this->createUserWithId(1);
        $this->security->method('getUser')->willReturn($user);

        // Mock entity metadata with createdBy field
        $this->mockEntityMetadataWithCreatedBy(\stdClass::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['o']);
        $queryBuilder->method('expr')->willReturn(new Expr());
        $queryBuilder->expects($this->once())->method('andWhere')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->willReturnSelf();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator('p'),
            \stdClass::class,
            new GetCollection(security: "is_granted('company:ownleads:viewown')"),
        );
    }

    public function testThrowsWhenTheOwnershipParentAssociationDoesNotExist(): void
    {
        $this->grantViewOwnOnly();

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturn(false);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getAssociationNames')->willReturn(['form', 'category']);

        $this->entityManager->method('getClassMetadata')->willReturn($metadata);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('declares #[OwnershipParent(\'nonexistent\')] but has no such association. Available: form, category.');

        $this->extension->applyToCollection(
            $this->createQueryBuilderWithRootAlias(),
            $this->createNameGenerator(),
            OwnershipParentMissingAssociation::class,
            new GetCollection(security: "is_granted('form:forms:viewown')"),
        );
    }

    public function testThrowsWhenTheOwnershipParentCarriesNoOwnership(): void
    {
        $this->grantViewOwnOnly();

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->method('hasField')->willReturn(false);
        $entityMetadata->method('hasAssociation')->willReturnCallback(
            fn (string $association): bool => 'parent' === $association
        );
        $entityMetadata->method('getAssociationTargetClass')->willReturn(UnownedParent::class);

        // the parent has neither an owner nor a createdBy
        $parentMetadata = $this->createMock(ClassMetadata::class);
        $parentMetadata->method('hasField')->willReturn(false);
        $parentMetadata->method('hasAssociation')->willReturn(false);

        $this->entityManager->method('getClassMetadata')->willReturnCallback(
            fn (string $class): ClassMetadata => UnownedParent::class === $class ? $parentMetadata : $entityMetadata
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('has neither an owner nor a createdBy field');

        $this->extension->applyToCollection(
            $this->createQueryBuilderWithRootAlias(),
            $this->createNameGenerator(),
            OwnershipParentWithoutOwnership::class,
            new GetCollection(security: "is_granted('form:forms:viewown')"),
        );
    }

    private function createQueryBuilderWithRootAlias(): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();
        $queryBuilder->method('getRootAliases')->willReturn(['o']);

        return $queryBuilder;
    }

    private function grantViewOwnOnly(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $permission): bool => str_ends_with($permission, 'viewown'));
        $this->security->method('getUser')->willReturn($this->createUserWithId(1));
    }

    private function createQueryBuilderExpectingNoCalls(): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->expects($this->never())->method('setParameter');

        return $queryBuilder;
    }

    private function createNameGenerator(string $generatedName = 'p'): QueryNameGeneratorInterface&MockObject
    {
        $generator = $this->createMock(QueryNameGeneratorInterface::class);
        $generator->method('generateParameterName')->willReturn($generatedName);

        return $generator;
    }

    private function createUserWithId(int $id): UserInterface
    {
        return new readonly class($id) implements UserInterface {
            public function __construct(
                private int $id,
            ) {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return (string) $this->id;
            }
        };
    }

    private function mockEntityMetadataWithCreatedBy(string $entityClass): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturnCallback(
            fn (string $field): bool => 'createdBy' === $field
        );
        $metadata->method('hasAssociation')->willReturn(false);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($metadata);
    }
}
