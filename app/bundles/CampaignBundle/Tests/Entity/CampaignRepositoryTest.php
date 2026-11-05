<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Campaign::class);

        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): DbalQueryBuilder => new DbalQueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.campaign.campaign.searchcommand.isexpired' => 'is:expired',
            'mautic.campaign.campaign.searchcommand.ispending' => 'is:pending',
            default                                            => $id,
        });

        $this->repository->autowireCommonRepository($translator);
    }

    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:expired', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertSame('(c.isPublished = :par1) AND (c.publishDown IS NOT NULL) AND (c.publishDown <> \'\') AND (c.publishDown < CURRENT_TIMESTAMP())', (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPendingFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:pending', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertSame('(c.isPublished = :par1) AND (c.publishUp IS NOT NULL) AND (c.publishUp <> \'\') AND (c.publishUp > CURRENT_TIMESTAMP())', (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $commands = $this->repository->getSearchCommands();
        $this->assertContains('mautic.campaign.campaign.searchcommand.isexpired', $commands);
        $this->assertContains('mautic.campaign.campaign.searchcommand.ispending', $commands);
    }

    #[DataProvider('ormSearchCommandDataProvider')]
    public function testAddSearchCommandWhereClauseBuildsFiltersWithOrmQueryBuilder(string $command, string $expected): void
    {
        $qb     = new OrmQueryBuilder($this->entityManager);
        $filter = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertStringContainsString($expected, (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function ormSearchCommandDataProvider(): iterable
    {
        yield ['is:expired', 'c.publishDown < CURRENT_TIMESTAMP()'];
        yield ['is:pending', 'c.publishUp > CURRENT_TIMESTAMP()'];
    }
}
