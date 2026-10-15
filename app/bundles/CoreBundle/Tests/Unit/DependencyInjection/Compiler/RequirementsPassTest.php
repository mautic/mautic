<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Compiler;

use Mautic\CoreBundle\DependencyInjection\Compiler\RequirementsPass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Process\Process;

final class RequirementsPassTest extends TestCase
{
    public function testCompilerDoesNotRequireResolvingTheConfiguredDriver(): void
    {
        if ([] === array_intersect(['mysql', 'pgsql'], \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('A supported PDO driver is required.');
        }
        $container = new ContainerBuilder();
        $container->setParameter('mautic.db_driver', '%env(nullable:resolve:MAUTIC_DB_DRIVER)%');

        (new RequirementsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('provideDriverExtensions')]
    public function testDriverAvailabilityInAnIsolatedPhpRuntime(?string $driver): void
    {
        $arguments = [PHP_BINARY, '-n', '-d', 'extension=pdo'];
        if ('pdo_mysql' === $driver) {
            $arguments[] = '-d';
            $arguments[] = 'extension=mysqlnd';
        }
        if (null !== $driver) {
            $arguments[] = '-d';
            $arguments[] = 'extension='.$driver;
        }
        $code = <<<'PHP'
            require $argv[1].'/vendor/autoload.php';
            try {
                (new Mautic\CoreBundle\DependencyInjection\Compiler\RequirementsPass())->process(new Symfony\Component\DependencyInjection\ContainerBuilder());
                echo json_encode(['drivers' => PDO::getAvailableDrivers(), 'accepted' => true]);
            } catch (Mautic\CoreBundle\Exception\MessageOnlyErrorHandlerException $exception) {
                echo json_encode(['drivers' => PDO::getAvailableDrivers(), 'accepted' => false]);
            }
            PHP;
        $arguments[] = '-r';
        $arguments[] = $code;
        $arguments[] = dirname(__DIR__, 7);
        $process = new Process($arguments);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(null === $driver ? [] : [substr($driver, 4)], $result['drivers']);
        $this->assertSame(null !== $driver, $result['accepted']);
    }

    public static function provideDriverExtensions(): iterable
    {
        yield 'PostgreSQL only' => ['pdo_pgsql'];
        yield 'MySQL only' => ['pdo_mysql'];
        yield 'no supported driver' => [null];
    }
}
