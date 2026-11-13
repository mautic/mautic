<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private FormRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Form::class);

        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): DbalQueryBuilder => new DbalQueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.form.form.searchcommand.isexpired'  => 'is:expired',
            'mautic.form.form.searchcommand.ispending'  => 'is:pending',
            'mautic.form.form.searchcommand.hasactions' => 'has:actions',
            default                                     => $id,
        });

        $this->repository->autowireCommonRepository($translator);
    }

    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:expired', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertSame('(f.isPublished = :par1) AND (f.publishDown IS NOT NULL) AND (f.publishDown < CURRENT_TIMESTAMP())', (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPendingFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:pending', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertSame('(f.isPublished = :par1) AND (f.publishUp IS NOT NULL) AND (f.publishUp > CURRENT_TIMESTAMP())', (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $commands = $this->repository->getSearchCommands();
        $this->assertContains('mautic.form.form.searchcommand.isexpired', $commands);
        $this->assertContains('mautic.form.form.searchcommand.ispending', $commands);
    }

    public function testAddSearchCommandWhereClauseHandlesHasActionsFilter(): void
    {
        $this->entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $this->entityManager->method('createQueryBuilder')->willReturnCallback(fn (): OrmQueryBuilder => new OrmQueryBuilder($this->entityManager));

        $qb     = new OrmQueryBuilder($this->entityManager);
        $filter = (object) ['command' => 'has:actions', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr] = $method->invoke($this->repository, $qb, $filter);

        $this->assertStringContainsString('Mautic\\FormBundle\\Entity\\Action a WHERE a.form = f', (string) $expr);
    }

    public function testGetSearchCommandsContainsHasActions(): void
    {
        $this->assertContains('mautic.form.form.searchcommand.hasactions', $this->repository->getSearchCommands());
    }

    #[DataProvider('ormSearchCommandDataProvider')]
    public function testAddSearchCommandWhereClauseBuildsFiltersWithOrmQueryBuilder(string $command, string $expected): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $qb            = new OrmQueryBuilder($entityManager);
        $filter = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertStringContainsString($expected, (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function ormSearchCommandDataProvider(): iterable
    {
        yield ['is:expired', 'f.publishDown < CURRENT_TIMESTAMP()'];
        yield ['is:pending', 'f.publishUp > CURRENT_TIMESTAMP()'];
    }
}
