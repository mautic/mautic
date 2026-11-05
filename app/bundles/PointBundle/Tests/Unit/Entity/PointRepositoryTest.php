<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\PointBundle\Entity\PointRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointRepositoryTest extends TestCase
{
    public function testTypeGainCommandBuildsPositiveDeltaExpression(): void
    {
        $repository = $this->createRepository();
        $filter     = $this->createFilter('type', 'gain');

        [$expr, $params] = $repository->exposeAddSearchCommandWhereClause(new DummyQueryBuilder(), $filter);

        self::assertSame(['gt', 'p.delta', '0'], $expr);
        self::assertSame([], $params);
    }

    public function testTypeLossCommandBuildsNegativeDeltaExpression(): void
    {
        $repository = $this->createRepository();
        $filter     = $this->createFilter('type', 'loss');

        [$expr, $params] = $repository->exposeAddSearchCommandWhereClause(new DummyQueryBuilder(), $filter);

        self::assertSame(['lt', 'p.delta', '0'], $expr);
        self::assertSame([], $params);
    }

    public function testNegatedTypeGainCommandBuildsNonPositiveDeltaExpression(): void
    {
        $repository      = $this->createRepository();
        $filter          = $this->createFilter('type', 'gain');
        $filter->not     = true;

        [$expr, $params] = $repository->exposeAddSearchCommandWhereClause(new DummyQueryBuilder(), $filter);

        self::assertSame(['lte', 'p.delta', '0'], $expr);
        self::assertSame([], $params);
    }

    public function testCommandListIncludesTypeOptions(): void
    {
        $repository = $this->createRepository();

        $commands = $repository->exposeGetSearchCommands();

        self::assertContains('mautic.point.searchcommand.type_gain.filter', $commands);
        self::assertContains('mautic.point.searchcommand.type_loss.filter', $commands);
    }

    private function createRepository(): TestPointRepository
    {
        /** @var ManagerRegistry&MockObject $registry */
        $registry   = $this->createMock(ManagerRegistry::class);
        $repository = new TestPointRepository($registry);

        $translator = $this->createTranslator();
        $repository->autowireCommonRepository($translator);

        return $repository;
    }

    private function createTranslator(): TranslatorInterface
    {
        $translations = [
            'mautic.point.searchcommand.type'       => 'type',
            'mautic.point.searchcommand.type_gain'  => 'gain',
            'mautic.point.searchcommand.type_loss'  => 'loss',
            'mautic.project.searchcommand.name'     => 'project',
        ];

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static function (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null) use ($translations): string {
                return $translations[$id] ?? $id;
            }
        );

        return $translator;
    }

    private function createFilter(string $command, string $string): \stdClass
    {
        return (object) [
            'command' => $command,
            'string'  => $string,
            'not'     => false,
            'strict'  => false,
        ];
    }
}

final class TestPointRepository extends PointRepository
{
    public function __construct(ManagerRegistry $registry)
    {
    }

    public function exposeAddSearchCommandWhereClause($q, $filter): array
    {
        return parent::addSearchCommandWhereClause($q, $filter);
    }

    public function exposeGetSearchCommands(): array
    {
        return parent::getSearchCommands();
    }
}

final class DummyQueryBuilder
{
    private DummyQueryExpr $expr;

    public function __construct()
    {
        $this->expr = new DummyQueryExpr();
    }

    public function expr(): DummyQueryExpr
    {
        return $this->expr;
    }
}

final class DummyQueryExpr
{
    public function gt(string $field, string $value): array
    {
        return ['gt', $field, $value];
    }

    public function lt(string $field, string $value): array
    {
        return ['lt', $field, $value];
    }

    public function lte(string $field, string $value): array
    {
        return ['lte', $field, $value];
    }

    public function gte(string $field, string $value): array
    {
        return ['gte', $field, $value];
    }
}
