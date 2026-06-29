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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

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
            ->willReturnCallback(fn (string $p) => 'lead:leads:viewown' === $p);

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
            ->willReturnCallback(fn (string $p) => 'lead:leads:viewother' === $p);

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
        $this->security->expects(self::never())->method('isGranted');

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
        $this->security->expects(self::never())->method('isGranted');

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
        $this->security->expects(self::never())->method('isGranted');

        $queryBuilder = $this->createQueryBuilderExpectingNoCalls();

        $this->extension->applyToCollection(
            $queryBuilder,
            $this->createNameGenerator(),
            \stdClass::class,
            null,
        );
    }

    public function testOtherPermissionDerivationDoesNotCorruptOwnInMiddleOfString(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $p) => 'company:ownleads:viewown' === $p);

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

    private function createQueryBuilderExpectingNoCalls(): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::never())->method('andWhere');
        $queryBuilder->expects(self::never())->method('setParameter');

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
        return new class($id) implements UserInterface {
            public function __construct(private readonly int $id)
            {
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
            fn (string $field) => 'createdBy' === $field
        );
        $metadata->method('hasAssociation')->willReturn(false);

        $this->entityManager->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($metadata);
    }
}
