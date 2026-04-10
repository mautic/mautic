<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Campaign::class);

        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new DbalQueryBuilder($this->connection));
        $this->entityManager->method('getExpressionBuilder')->willReturn(new Expr());

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn ($id) => match ($id) {
            'mautic.campaign.campaign.searchcommand.list'      => 'list',
            'mautic.campaign.campaign.searchcommand.isexpired' => 'is:expired',
            'mautic.campaign.campaign.searchcommand.ispending' => 'is:pending',
            default                                            => $id,
        });
        $this->repository->setTranslator($translator);
    }

    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:expired', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        self::assertSame(
            '(c.isPublished = :par1) AND (c.publishDown IS NOT NULL) AND (c.publishDown <> \'\') AND (c.publishDown < CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPendingFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:pending', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        self::assertSame(
            '(c.isPublished = :par1) AND (c.publishUp IS NOT NULL) AND (c.publishUp <> \'\') AND (c.publishUp > CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesListAliasFilter(): void
    {
        $qb     = new OrmQueryBuilder($this->entityManager);
        $qb->select('c')->from(Campaign::class, 'c');
        $filter = (object) ['command' => 'list', 'string' => 'people', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        self::assertSame('l.alias = :par1', (string) $expr);
        self::assertSame(['par1' => 'people'], $params);
        self::assertArrayHasKey('c', $qb->getDQLPart('join'));
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $commands = $this->repository->getSearchCommands();
        self::assertContains('mautic.campaign.campaign.searchcommand.list', $commands);
        self::assertContains('mautic.campaign.campaign.searchcommand.isexpired', $commands);
        self::assertContains('mautic.campaign.campaign.searchcommand.ispending', $commands);
    }
}
