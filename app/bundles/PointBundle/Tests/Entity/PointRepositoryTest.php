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
        $translator->method('trans')->willReturnCallback(fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => match ($id) {
            'mautic.project.searchcommand.name'              => 'en_US' === $locale ? 'project' : 'projekt',
            'mautic.point.searchcommand.isrepeatable'        => 'en_US' === $locale ? 'is:repeatable' : 'ist:wiederholbar',
            'mautic.point.searchcommand.type_gain.filter'    => 'en_US' === $locale ? 'type:gain' : 'typ:gewinn',
            'mautic.point.searchcommand.type_loss.filter'    => 'en_US' === $locale ? 'type:loss' : 'typ:verlust',
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
        yield 'localized repeatable' => ['ist:wiederholbar', '', false, true, false];
        yield 'negated repeatable' => ['is:repeatable', '', true, true, true];
    }

    #[DataProvider('deltaTypeFilters')]
    public function testAddSearchCommandWhereClauseHandlesDeltaType(string $command, bool $not, string $expectedExpression): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) [
            'command' => $command,
            'string'  => '',
            'not'     => $not,
            'strict'  => false,
        ];

        $method = new \ReflectionMethod(PointRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $this->assertSame($expectedExpression, (string) $expr);
        $this->assertSame([], $params);
    }

    /**
     * @return iterable<array{string,bool,string}>
     */
    public static function deltaTypeFilters(): iterable
    {
        yield 'gain' => ['type:gain', false, 'p.delta > 0'];
        yield 'localized gain' => ['typ:gewinn', false, 'p.delta > 0'];
        yield 'loss' => ['type:loss', false, 'p.delta < 0'];
        yield 'localized loss' => ['typ:verlust', false, 'p.delta < 0'];
        yield 'negated gain' => ['type:gain', true, 'p.delta <= 0'];
        yield 'negated loss' => ['type:loss', true, 'p.delta >= 0'];
    }

    public function testCommandListIncludesTypeAndRepeatableOptions(): void
    {
        $commands = $this->getRepository()->getSearchCommands();

        $this->assertContains('mautic.point.searchcommand.type_gain.filter', $commands);
        $this->assertContains('mautic.point.searchcommand.type_loss.filter', $commands);
        $this->assertContains('mautic.point.searchcommand.isrepeatable', $commands);
    }
}
