<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Project::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): DbalQueryBuilder => new DbalQueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => match ($id) {
                'mautic.project.searchcommand.hasemails'     => 'has:emails',
                'mautic.project.searchcommand.hascampaigns'  => 'has:campaigns',
                'mautic.project.searchcommand.haspages'      => 'has:pages',
                'mautic.project.searchcommand.hasassets'     => 'has:assets',
                default                                      => $id,
            }
        );

        $this->repository->autowireCommonRepository($translator);
    }

    public function testGetSearchCommandsIncludesAssociationFilters(): void
    {
        $commands = $this->repository->getSearchCommands();

        $this->assertContains('mautic.project.searchcommand.hasemails', $commands);
        $this->assertContains('mautic.project.searchcommand.hascampaigns', $commands);
        $this->assertContains('mautic.project.searchcommand.haspages', $commands);
        $this->assertContains('mautic.project.searchcommand.hasassets', $commands);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('associationFilterProvider')]
    public function testAddSearchCommandWhereClauseHandlesAssociationFilters(string $command, string $expectedTable): void
    {
        $this->connection->expects($this->never())->method('executeQuery');

        $queryBuilder = $this->connection->createQueryBuilder();
        $filter       = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);

        $expression = (string) $expr;
        $this->assertStringContainsString('p.id IN (SELECT projectxref.project_id FROM ', $expression);
        $this->assertStringContainsString($expectedTable.' projectxref', $expression);
        $this->assertSame([], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function associationFilterProvider(): iterable
    {
        yield ['has:emails', 'email_projects_xref'];
        yield ['has:campaigns', 'campaign_projects_xref'];
        yield ['has:pages', 'page_projects_xref'];
        yield ['has:assets', 'asset_projects_xref'];
    }

    public function testAssociationFilterSupportsNegation(): void
    {
        $this->connection->expects($this->never())->method('executeQuery');

        $queryBuilder = $this->connection->createQueryBuilder();
        $filter       = (object) ['command' => 'has:emails', 'string' => '', 'not' => true, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);

        $this->assertStringContainsString('p.id NOT IN (SELECT projectxref.project_id FROM ', (string) $expr);
        $this->assertStringContainsString('email_projects_xref projectxref', (string) $expr);
        $this->assertSame([], $params);
    }

    public function testOrmAssociationFilterUsesCorrelatedSubquery(): void
    {
        $this->entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $subQuery = new OrmQueryBuilder($this->entityManager);
        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($subQuery);

        $queryBuilder = new OrmQueryBuilder($this->entityManager);
        $filter       = (object) ['command' => 'has:emails', 'string' => '', 'not' => true, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);
        $subQueryDql     = $subQuery->getDQL();

        $this->assertStringContainsString('NOT(EXISTS', (string) $expr);
        $this->assertStringContainsString('FROM Mautic\\EmailBundle\\Entity\\Email associated', $subQueryDql);
        $this->assertStringContainsString('INNER JOIN associated.projects associatedProject', $subQueryDql);
        $this->assertStringContainsString('associatedProject.id = p.id', $subQueryDql);
        $this->assertSame([], $params);
    }
}
