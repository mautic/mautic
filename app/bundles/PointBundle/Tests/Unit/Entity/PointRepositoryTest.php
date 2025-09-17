<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\PointBundle\Entity\PointRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PointRepositoryTest extends TestCase
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

    public function testNegatedTypeGainCommandWrapsExpressionInNot(): void
    {
        $repository      = $this->createRepository();
        $filter          = $this->createFilter('type', 'gain');
        $filter->not     = true;

        [$expr, $params] = $repository->exposeAddSearchCommandWhereClause(new DummyQueryBuilder(), $filter);

        self::assertSame(['not', ['gt', 'p.delta', '0']], $expr);
        self::assertSame([], $params);
    }

    public function testCommandListIncludesTypeOptions(): void
    {
        $repository = $this->createRepository();

        $commands = $repository->exposeGetSearchCommands();

        self::assertArrayHasKey('mautic.point.searchcommand.type', $commands);
        self::assertSame(
            [
                'mautic.point.searchcommand.type_gain',
                'mautic.point.searchcommand.type_loss',
            ],
            $commands['mautic.point.searchcommand.type']
        );
    }

    private function createRepository(): TestPointRepository
    {
        /** @var ManagerRegistry&MockObject $registry */
        $registry   = $this->createMock(ManagerRegistry::class);
        $repository = new TestPointRepository($registry);

        $translator = $this->createTranslator();
        $repository->setTranslator($translator);

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

class TestPointRepository extends PointRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // Avoid invoking the parent constructor to keep the repository lightweight for unit tests.
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

class DummyQueryBuilder
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

class DummyQueryExpr
{
    public function gt(string $field, string $value): array
    {
        return ['gt', $field, $value];
    }

    public function lt(string $field, string $value): array
    {
        return ['lt', $field, $value];
    }

    public function not(array $expr): array
    {
        return ['not', $expr];
    }
}
