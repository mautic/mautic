<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\ReportRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): ReportRepository
    {
        $repository = $this->configureRepository(Report::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => match ($id) {
                'mautic.report.report.searchcommand.source'  => 'source',
                default                                      => $id,
            }
        );

        $repository->autowireCommonRepository($translator);
        $this->entityManager->method('getExpressionBuilder')->willReturn(new Expr());

        return $repository;
    }

    public function testAddSearchCommandWhereClauseFiltersBySource(): void
    {
        $repository = $this->getRepository();
        $qb         = new QueryBuilder($this->entityManager);
        $filter     = (object) ['command' => 'source', 'string' => 'leads', 'not' => false, 'strict' => true];

        $method = new \ReflectionMethod(ReportRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $parameterName = array_key_first($params);

        $this->assertNotFalse($expr);
        $this->assertNotNull($parameterName);
        $this->assertStringStartsWith('par', $parameterName);
        $this->assertSame('leads', $params[$parameterName]);
        $this->assertSame(sprintf('r.source = :%s', $parameterName), (string) $expr);
    }

    public function testAddSearchCommandWhereClauseSupportsNegation(): void
    {
        $repository = $this->getRepository();
        $qb         = new QueryBuilder($this->entityManager);
        $filter     = (object) ['command' => 'source', 'string' => 'campaign_lead_event_log', 'not' => true, 'strict' => true];

        $method = new \ReflectionMethod(ReportRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $parameterName = array_key_first($params);

        $this->assertNotFalse($expr);
        $this->assertNotNull($parameterName);
        $this->assertStringStartsWith('par', $parameterName);
        $this->assertSame('campaign_lead_event_log', $params[$parameterName]);
        $this->assertSame(sprintf('NOT(r.source = :%s)', $parameterName), (string) $expr);
    }

    public function testAddSearchCommandWhereClauseSupportsDelimitedSources(): void
    {
        $repository = $this->getRepository();
        $qb         = new QueryBuilder($this->entityManager);
        $filter     = (object) ['command' => 'source', 'string' => 'segment.log', 'not' => false, 'strict' => true];

        $method = new \ReflectionMethod(ReportRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $parameterName = array_key_first($params);

        $this->assertNotFalse($expr);
        $this->assertNotNull($parameterName);
        $this->assertStringStartsWith('par', $parameterName);
        $this->assertSame('segment.log', $params[$parameterName]);
        $this->assertSame(sprintf('r.source = :%s', $parameterName), (string) $expr);
    }

    public function testGetSearchCommandsContainsSourceFilter(): void
    {
        $repository = $this->getRepository();

        $this->assertContains('mautic.report.report.searchcommand.source', $repository->getSearchCommands());
    }
}
