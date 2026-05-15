<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use ArrayObject;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\LeadBundle\Entity\TimelineTrait;
use Mautic\LeadBundle\Entity\UtmTagRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimelineTrait::class)]
class TimelineTraitTest extends TestCase
{
    private UtmTagRepository&MockObject $repository;

    private \ReflectionMethod $getTimelineResults;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(UtmTagRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTableAlias'])
            ->getMock();

        $this->repository->method('getTableAlias')->willReturn('ut');

        $this->getTimelineResults = new \ReflectionMethod($this->repository, 'getTimelineResults');
    }

    /**
     * Build a QueryBuilder mock that records ORDER BY clauses into $orderByParts.
     * Using ArrayObject so the closure and caller share the same mutable object
     * (avoids reference-breaking on array destructuring).
     *
     * @return array{0: QueryBuilder&MockObject, 1: \ArrayObject<int, string>}
     */
    private function buildQueryBuilderSpy(): array
    {
        /** @var \ArrayObject<int, string> $orderByParts */
        $orderByParts = new \ArrayObject();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();

        $qb->method('orderBy')->willReturnCallback(
            static function (string $sort, string $dir) use ($qb, $orderByParts): QueryBuilder {
                $orderByParts->append($sort.' '.$dir);

                return $qb;
            }
        );

        $qb->method('addOrderBy')->willReturnCallback(
            static function (string $sort, string $dir) use ($qb, $orderByParts): QueryBuilder {
                $orderByParts->append($sort.' '.$dir);

                return $qb;
            }
        );

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);
        $qb->method('executeQuery')->willReturn($result);

        return [$qb, $orderByParts];
    }

    public function testOrderByDirAscIsPassedThrough(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'ASC']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.date_added ASC'], $orderByParts->getArrayCopy());
    }

    public function testOrderByDirDescIsPassedThrough(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'DESC']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.date_added DESC'], $orderByParts->getArrayCopy());
    }

    public function testOrderByDirLowercaseIsNormalized(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'asc']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.date_added ASC'], $orderByParts->getArrayCopy());
    }

    /**
     * Any value that is not 'ASC' (case-insensitive) must be coerced to 'DESC'
     * to prevent SQL injection via an arbitrary string in the ORDER BY direction.
     */
    public function testMaliciousOrderByDirWithSleepIsForcedToDesc(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'ASC,(SELECT SLEEP(5))--']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.date_added DESC'], $orderByParts->getArrayCopy());
    }

    public function testMaliciousOrderByDirWithUnionIsForcedToDesc(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'DESC UNION SELECT password FROM users--']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.date_added DESC'], $orderByParts->getArrayCopy());
    }

    public function testSecondaryOrderingUsesWhitelistedDir(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['timestamp', 'ASC,(SELECT 1)']],
            'ut.utm_campaign',
            'ut.date_added',
            [],
            [],
            null,
            'ut.id',
        );

        $this->assertSame(['ut.date_added DESC', 'ut.id DESC'], $orderByParts->getArrayCopy());
    }

    public function testEventLabelOrderByRoutesToEventNameColumn(): void
    {
        [$qb, $orderByParts] = $this->buildQueryBuilderSpy();

        $this->getTimelineResults->invoke(
            $this->repository,
            $qb,
            ['order' => ['eventLabel', 'ASC']],
            'ut.utm_campaign',
            'ut.date_added',
        );

        $this->assertSame(['ut.utm_campaign ASC'], $orderByParts->getArrayCopy());
    }
}
