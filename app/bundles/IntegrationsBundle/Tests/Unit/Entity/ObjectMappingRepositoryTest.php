<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ObjectMappingRepositoryTest extends TestCase
{
    /**
     * @var MockObject|EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MockObject|ClassMetadata
     */
    private $classMetadata;

    /**
     * @var MockObject|AbstractQuery
     */
    private $query;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ObjectMappingRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->queryBuilder  = new QueryBuilder($this->entityManager);
        $this->repository    = new ObjectMappingRepository($this->entityManager, $this->classMetadata);

        // This is terrible, but the Query class is final and AbstractQuery doesn't have some methods used.
        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setParameters', 'getSingleResult', 'getSQL', '_doExecute'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->getMock();

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())
            ->method('setFirstResult')
            ->willReturnSelf();

        $this->query->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
    }

    public function testDeleteEntitiesForObject(): void
    {
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('DELETE Mautic\IntegrationsBundle\Entity\ObjectMapping m WHERE m.internalObjectName = :internalObject AND m.internalObjectId = :internalObjectId')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('setParameters')
            ->with($this->callback(function (ArrayCollection $collection) {
                /** @var Parameter $parameter */
                $parameter = $collection[0];
                $this->assertSame('internalObject', $parameter->getName());
                $this->assertSame('company', $parameter->getValue());

                /** @var Parameter $parameter */
                $parameter = $collection[1];
                $this->assertSame('internalObjectId', $parameter->getName());
                $this->assertSame(123, $parameter->getValue());

                return true;
            }))
            ->willReturnSelf();

        // Stopping early to avoid Mocking hell. We have what we needed.
        $this->query->expects($this->once())
            ->method('_doExecute')
            ->willReturn(0);

        $this->repository->deleteEntitiesForObject(123, 'company');
    }
}
