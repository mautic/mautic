<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Test;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MauticMysqlTestCaseTest extends TestCase
{
    /**
     * @param list<string> $expectedOptions
     */
    #[DataProvider('dumpClientVersions')]
    public function testDumpOptionsSupportClientProduct(string $version, array $expectedOptions): void
    {
        $options = (new \ReflectionMethod(MauticMysqlTestCase::class, 'getMysqlDumpCompatibilityOptions'))->invoke($this->createStub(MauticMysqlTestCase::class), $version);

        $this->assertSame($expectedOptions, $options);
    }

    public static function dumpClientVersions(): iterable
    {
        yield 'MariaDB client bundled with DDEV' => [
            'mysqldump  Ver 10.19 Distrib 10.11.16-MariaDB, for debian-linux-gnu (x86_64)',
            ['--no-defaults', '--no-tablespaces'],
        ];
        yield 'Oracle MySQL client' => [
            'mysqldump  Ver 8.4.8 for Linux on x86_64 (MySQL Community Server - GPL)',
            ['--no-defaults', '--no-tablespaces', '--set-gtid-purged=OFF'],
        ];
    }
}
