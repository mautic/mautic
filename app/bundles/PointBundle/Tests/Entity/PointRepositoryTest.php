<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\PointRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): PointRepository
    {
        $repository = $this->configureRepository(Point::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.project.searchcommand.name'              => 'project',
            'mautic.point.searchcommand.isrepeatable'        => 'is:repeatable',
            default                                          => $id,
        });
        $repository->autowireCommonRepository($translator);

        return $repository;
    }

    #[DataProvider('repeatableFilters')]
    public function testAddSearchCommandWhereClauseHandlesRepeatable(
        string $command,
        string $string,
        bool $not,
        bool $expectedValue,
        bool $expectsNegation,
    ): void {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) [
            'command' => $command,
            'string'  => $string,
            'not'     => $not,
            'strict'  => false,
        ];

        $method = new \ReflectionMethod(PointRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $this->assertNotEmpty($params);
        $paramName = array_key_first($params);

        $expectedExpression = $expectsNegation
            ? sprintf('p.repeatable <> :%s', $paramName)
            : sprintf('p.repeatable = :%s', $paramName);

        $this->assertSame($expectedExpression, (string) $expr);

        $this->assertSame($expectedValue, $params[$paramName]);
    }

    /**
     * @return iterable<array{string,string,bool,bool,bool}>
     */
    public static function repeatableFilters(): iterable
    {
        yield 'repeatable' => ['is:repeatable', '', false, true, false];
        yield 'negated repeatable' => ['is:repeatable', '', true, true, true];
    }
}
