<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class InstallBootstrapTest extends TestCase
{
    #[DataProvider('providePrefixes')]
    public function testConsoleResolvesInstallerPrefixBeforeBootingTheKernel(string $prefix): void
    {
        $root = dirname(__DIR__, 5);
        $code = <<<'PHP'
            require 'vendor/autoload.php';
            $_SERVER['argv'] = ['bin/console', 'mautic:install', 'http://localhost', '--db_table_prefix='.$argv[1]];
            $application = include 'app/console-application.php';
            echo json_encode(['prefix' => MAUTIC_TABLE_PREFIX, 'installer' => defined('MAUTIC_INSTALLER')]);
            PHP;
        $process = new Process([PHP_BINARY, '-r', $code, $prefix], $root);
        $process->mustRun();

        $this->assertSame(['prefix' => $prefix, 'installer' => true], json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function providePrefixes(): iterable
    {
        yield ['pgcli_'];
        yield [''];
    }
}
