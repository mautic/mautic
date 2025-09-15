<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(FocusRepository::class)]
class FocusRepositoryTest extends TestCase
{
    /** @var FocusRepository&MockObject */
    private FocusRepository $repository;
    /** @var EntityManager&MockObject */
    private MockObject $entityManager;
    /** @var Translator&MockObject */
    private MockObject $translator;
    /** @var QueryBuilder&MockObject */
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->translator    = $this->createMock(Translator::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->entityManager);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->entityManager->method('getClassMetadata')->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(FocusRepository::class)
            ->setConstructorArgs([$managerRegistry, Focus::class])
            ->onlyMethods(['addStandardSearchCommandWhereClause', 'generateRandomParameterName', 'getStandardSearchCommands', 'addStandardCatchAllWhereClause'])
            ->getMock();

        // Use reflection to set the translator property
        $reflection         = new \ReflectionClass($this->repository);
        $translatorProperty = $reflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translatorProperty->setValue($this->repository, $this->translator);
    }

    public function testAddSearchCommandWhereClauseWithStyleBar(): void
    {
        $filter = $this->createFilter('style:bar');

        // Set up translator to return the expected command for both default and en_US locale
        $this->translator->method('trans')
            ->willReturnCallback(function ($key, $params = [], $domain = null, $locale = null) {
                if ('mautic.focus.focus.searchcommand.stylebar' === $key) {
                    return 'style:bar';
                }

                return 'unknown';
            });

        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn([null, []]);
        $this->repository->method('generateRandomParameterName')
            ->willReturn('param1');

        $expr = $this->createMock(\Doctrine\ORM\Query\Expr::class);
        $expr->method('eq')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($expr);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('param1', $result[1]);
        $this->assertEquals('bar', $result[1]['param1']);
    }

    public function testAddSearchCommandWhereClauseWithStyleModal(): void
    {
        $filter = $this->createFilter('style:modal');

        // Set up translator to return the expected command for both default and en_US locale
        $this->translator->method('trans')
            ->willReturnCallback(function ($key, $params = [], $domain = null, $locale = null) {
                if ('mautic.focus.focus.searchcommand.stylemodal' === $key) {
                    return 'style:modal';
                }

                return 'unknown';
            });

        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn([null, []]);
        $this->repository->method('generateRandomParameterName')
            ->willReturn('param2');

        $expr = $this->createMock(\Doctrine\ORM\Query\Expr::class);
        $expr->method('eq')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($expr);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('param2', $result[1]);
        $this->assertEquals('modal', $result[1]['param2']);
    }

    public function testAddSearchCommandWhereClauseWithStyleNotification(): void
    {
        $filter = $this->createFilter('style:notification');

        // Set up translator to return the expected command for both default and en_US locale
        $this->translator->method('trans')
            ->willReturnCallback(function ($key, $params = [], $domain = null, $locale = null) {
                if ('mautic.focus.focus.searchcommand.stylenotification' === $key) {
                    return 'style:notification';
                }

                return 'unknown';
            });

        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn([null, []]);
        $this->repository->method('generateRandomParameterName')
            ->willReturn('param3');

        $expr = $this->createMock(\Doctrine\ORM\Query\Expr::class);
        $expr->method('eq')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($expr);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('param3', $result[1]);
        $this->assertEquals('notification', $result[1]['param3']);
    }

    public function testAddSearchCommandWhereClauseWithStyleFullpage(): void
    {
        $filter = $this->createFilter('style:fullpage');

        // Set up translator to return the expected command for both default and en_US locale
        $this->translator->method('trans')
            ->willReturnCallback(function ($key, $params = [], $domain = null, $locale = null) {
                if ('mautic.focus.focus.searchcommand.stylefullpage' === $key) {
                    return 'style:fullpage';
                }

                return 'unknown';
            });

        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn([null, []]);
        $this->repository->method('generateRandomParameterName')
            ->willReturn('param4');

        $expr = $this->createMock(\Doctrine\ORM\Query\Expr::class);
        $expr->method('eq')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($expr);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('param4', $result[1]);
        $this->assertEquals('page', $result[1]['param4']);
    }

    public function testAddSearchCommandWhereClauseWithNotFilter(): void
    {
        $filter = $this->createFilter('style:bar', true);

        // Set up translator to return the expected command for both default and en_US locale
        $this->translator->method('trans')
            ->willReturnCallback(function ($key, $params = [], $domain = null, $locale = null) {
                if ('mautic.focus.focus.searchcommand.stylebar' === $key) {
                    return 'style:bar';
                }

                return 'unknown';
            });

        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn([null, []]);
        $this->repository->method('generateRandomParameterName')
            ->willReturn('param6');

        $expr = $this->createMock(\Doctrine\ORM\Query\Expr::class);
        $expr->method('eq')->willReturnSelf();
        $expr->method('not')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($expr);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testAddSearchCommandWhereClauseWithStandardCommand(): void
    {
        $filter = $this->createFilter('standard:command');
        $this->repository->method('addStandardSearchCommandWhereClause')
            ->willReturn(['standard_expr', ['param' => 'value']]);

        $result = $this->callProtectedMethod('addSearchCommandWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('standard_expr', $result[0]);
        $this->assertEquals(['param' => 'value'], $result[1]);
    }

    public function testGetSearchCommands(): void
    {
        $this->repository->method('getStandardSearchCommands')
            ->willReturn(['standard1', 'standard2']);

        $result = $this->repository->getSearchCommands();

        $expectedCommands = [
            'mautic.project.searchcommand.name',
            'mautic.focus.focus.searchcommand.stylebar',
            'mautic.focus.focus.searchcommand.stylemodal',
            'mautic.focus.focus.searchcommand.stylenotification',
            'mautic.focus.focus.searchcommand.stylefullpage',
            'standard1',
            'standard2',
        ];

        $this->assertEquals($expectedCommands, $result);
    }

    public function testGetTableAlias(): void
    {
        $this->assertEquals('f', $this->repository->getTableAlias());
    }

    public function testGetDefaultOrder(): void
    {
        $result = $this->callProtectedMethod('getDefaultOrder', []);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]);
        $this->assertEquals('f.name', $result[0][0]);
        $this->assertEquals('ASC', $result[0][1]);
    }

    public function testAddCatchAllWhereClause(): void
    {
        $filter = $this->createFilter('test');
        $this->repository->method('addStandardCatchAllWhereClause')
            ->willReturn(['expr', ['param' => 'value']]);

        $result = $this->callProtectedMethod('addCatchAllWhereClause', [$this->queryBuilder, $filter]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('expr', $result[0]);
        $this->assertEquals(['param' => 'value'], $result[1]);
    }

    public function testIsTranslatedCommand(): void
    {
        $this->translator->method('trans')
            ->willReturn('translated');

        $result = $this->callProtectedMethod('isTranslatedCommand', ['translated', ['test.key']]);
        $this->assertTrue($result);

        $result = $this->callProtectedMethod('isTranslatedCommand', ['unknown', ['test.key']]);
        $this->assertFalse($result);
    }

    public function testGetStyleValueFromCommand(): void
    {
        $styleMapping = ['test.key' => 'value'];
        $this->translator->method('trans')
            ->willReturn('translated');

        $result = $this->callProtectedMethod('getStyleValueFromCommand', ['translated', $styleMapping]);
        $this->assertEquals('value', $result);

        $result = $this->callProtectedMethod('getStyleValueFromCommand', ['unknown', $styleMapping]);
        $this->assertNull($result);
    }

    private function createFilter(string $command, bool $not = false): \stdClass
    {
        $filter          = new \stdClass();
        $filter->command = $command;
        $filter->string  = 'test';
        $filter->not     = $not;
        $filter->strict  = false;

        return $filter;
    }

    /**
     * @param array<int, mixed> $args
     */
    private function callProtectedMethod(string $methodName, array $args): mixed
    {
        $reflection = new \ReflectionClass($this->repository);
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->repository, $args);
    }
}
