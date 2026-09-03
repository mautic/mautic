<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UTCDateTimeTypeTest extends TestCase
{
    private UTCDateTimeType $type;

    protected function setUp(): void
    {
        $this->type = new UTCDateTimeType();
    }

    /**
     * @param class-string<AbstractPlatform> $platformClass
     * @param array<string, int>             $column
     */
    #[DataProvider('sqlDeclarationProvider')]
    public function testGetSQLDeclaration(string $platformClass, array $column, string $expectedSql, bool $expectFallback): void
    {
        $platform = $this->createMock($platformClass);

        if ($expectFallback) {
            $platform->expects($this->once())
                ->method('getDateTimeTypeDeclarationSQL')
                ->with($column)
                ->willReturn($expectedSql);
        }

        $this->assertSame($expectedSql, $this->type->getSQLDeclaration($column, $platform));
    }

    /**
     * @return iterable<string, array{
     *     0: class-string<AbstractPlatform>,
     *     1: array<string, int>|array<empty>,
     *     2: string,
     *     3: bool
     * }>
     */
    public static function sqlDeclarationProvider(): iterable
    {
        yield 'MySQL with valid precision' => [
            MySQLPlatform::class,
            ['precision' => 3],
            'DATETIME(3)',
            false,
        ];

        yield 'PostgreSQL with valid precision' => [
            PostgreSQLPlatform::class,
            ['precision' => 4],
            'TIMESTAMP(4) WITHOUT TIME ZONE',
            false,
        ];

        yield 'MySQL with precision too high' => [
            MySQLPlatform::class,
            ['precision' => 7],
            'DATETIME',
            true,
        ];

        yield 'MySQL with precision too low' => [
            MySQLPlatform::class,
            ['precision' => 0],
            'DATETIME',
            true,
        ];

        yield 'MySQL with no precision' => [
            MySQLPlatform::class,
            [],
            'DATETIME',
            true,
        ];

        yield 'PostgreSQL with precision too high' => [
            PostgreSQLPlatform::class,
            ['precision' => 7],
            'TIMESTAMP WITHOUT TIME ZONE',
            true,
        ];

        yield 'PostgreSQL with precision too low' => [
            PostgreSQLPlatform::class,
            ['precision' => 0],
            'TIMESTAMP WITHOUT TIME ZONE',
            true,
        ];

        yield 'PostgreSQL with no precision' => [
            PostgreSQLPlatform::class,
            [],
            'TIMESTAMP WITHOUT TIME ZONE',
            true,
        ];
    }
}
