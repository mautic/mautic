<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
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
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

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

    /**
     * @param array<int> $associatedProjectIds
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('associationFilterProvider')]
    public function testAddSearchCommandWhereClauseHandlesAssociationFilters(string $command, string $expectedTable, array $associatedProjectIds, string $expectedExpression): void
    {
        $this->result->method('fetchFirstColumn')->willReturn($associatedProjectIds);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->callback(fn (string $sql): bool => str_contains($sql, $expectedTable)),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($this->result);

        $queryBuilder = $this->connection->createQueryBuilder();
        $filter       = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);

        $this->assertSame($expectedExpression, (string) $expr);
        $this->assertSame([], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<int>, 3: string}>
     */
    public static function associationFilterProvider(): iterable
    {
        yield ['has:emails', 'email_projects_xref', [1, 2], 'p.id IN (1, 2)'];
        yield ['has:campaigns', 'campaign_projects_xref', [5], 'p.id IN (5)'];
        yield ['has:pages', 'page_projects_xref', [8, 13, 21], 'p.id IN (8, 13, 21)'];
        yield ['has:assets', 'asset_projects_xref', [42], 'p.id IN (42)'];
    }

    public function testAssociationFilterSupportsNegation(): void
    {
        $this->result->method('fetchFirstColumn')->willReturn([9]);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->callback(fn (string $sql): bool => str_contains($sql, 'email_projects_xref')),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($this->result);

        $queryBuilder = $this->connection->createQueryBuilder();
        $filter       = (object) ['command' => 'has:emails', 'string' => '', 'not' => true, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);

        $this->assertSame('p.id NOT IN (9)', (string) $expr);
        $this->assertSame([], $params);
    }

    public function testAssociationFilterFallsBackWhenNoAssociationsExist(): void
    {
        $this->result->method('fetchFirstColumn')->willReturn([]);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->callback(fn (string $sql): bool => str_contains($sql, 'asset_projects_xref')),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($this->result);

        $queryBuilder = $this->connection->createQueryBuilder();
        $filter       = (object) ['command' => 'has:assets', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(ProjectRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $queryBuilder, $filter);

        $this->assertSame('p.id IN (0)', (string) $expr);
        $this->assertSame([], $params);
    }
}
