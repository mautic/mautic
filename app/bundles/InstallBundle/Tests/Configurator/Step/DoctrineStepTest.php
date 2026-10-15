<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Configurator\Step;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\InstallBundle\Configurator\Form\DoctrineStepType;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class DoctrineStepTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('connectionDefaultsProvider')]
    public function testNormalizesConnectionDefaults(array $parameters, string $driver, int|string $port, string $charset): void
    {
        $configurator = $this->createStub(Configurator::class);
        $configurator->method('getParameters')->willReturn($parameters);
        $step = new DoctrineStep($configurator);

        $this->assertSame($driver, $step->driver);
        $this->assertSame($port, $step->port);
        $this->assertSame($charset, $step->charset);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, int|string, string}>
     */
    public static function connectionDefaultsProvider(): iterable
    {
        $drivers = DoctrineStep::getDriverKeys();
        $driver  = in_array('pdo_mysql', $drivers, true) ? 'pdo_mysql' : ($drivers[0] ?? 'pdo_mysql');
        yield 'available driver' => [[], $driver, 'pdo_pgsql' === $driver ? 5432 : 3306, 'pdo_pgsql' === $driver ? 'UTF8' : 'utf8mb4'];
        yield 'mysql' => [['db_driver' => 'pdo_mysql'], 'pdo_mysql', 3306, 'utf8mb4'];
        yield 'postgres' => [['db_driver' => 'pdo_pgsql'], 'pdo_pgsql', 5432, 'UTF8'];
        yield 'blank postgres settings' => [['db_driver' => 'pdo_pgsql', 'db_port' => '', 'db_charset' => ''], 'pdo_pgsql', 5432, 'UTF8'];
        yield 'null postgres settings' => [['db_driver' => 'pdo_pgsql', 'db_port' => null, 'db_charset' => null], 'pdo_pgsql', 5432, 'UTF8'];
        yield 'custom postgres port' => [['db_driver' => 'pdo_pgsql', 'db_port' => '6543'], 'pdo_pgsql', '6543', 'UTF8'];
        yield 'explicit mysql port on postgres' => [['db_driver' => 'pdo_pgsql', 'db_port' => 3306], 'pdo_pgsql', 3306, 'UTF8'];
        yield 'mysql charset on postgres' => [['db_driver' => 'pdo_pgsql', 'db_charset' => 'utf8mb4'], 'pdo_pgsql', 5432, 'UTF8'];
        yield 'explicit postgres charset' => [['db_driver' => 'pdo_pgsql', 'db_charset' => 'LATIN1'], 'pdo_pgsql', 5432, 'LATIN1'];
    }

    public function testNormalizesSubmittedPostgresSettingsWithoutJavascript(): void
    {
        $configurator = $this->createStub(Configurator::class);
        $configurator->method('getParameters')->willReturn(['db_driver' => 'pdo_mysql']);
        $step          = new DoctrineStep($configurator);
        $step->driver  = 'pdo_pgsql';
        $step->port    = '';

        $parameters = $step->update($step);

        $this->assertSame(5432, $parameters['db_port']);
        $this->assertSame('UTF8', $parameters['db_charset']);

        $step->port = '3306';
        $this->assertSame('3306', $step->update($step)['db_port']);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('postgresFormDefaultsProvider')]
    public function testNormalizesPostgresFormBeforeConnectionValidation(array $parameters, string $driver, int|string $port, string $charset): void
    {
        $data = [];
        foreach ($parameters as $key => $value) {
            $data[substr($key, 3)] = $value;
        }
        $data['driver'] = $driver;

        $event   = new FormEvent($this->createStub(FormInterface::class), $data);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventListener')->with(
            FormEvents::PRE_SUBMIT,
            $this->callback(static function (callable $listener) use ($event): bool {
                $listener($event);

                return true;
            })
        )->willReturnSelf();

        (new DoctrineStepType())->buildForm($builder, []);

        $this->assertSame($port, $event->getData()['port']);
        $this->assertSame($charset, $event->getData()['charset']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, int|string, string}>
     */
    public static function postgresFormDefaultsProvider(): iterable
    {
        foreach (self::connectionDefaultsProvider() as $name => [$parameters, $driver, $port, $charset]) {
            if ('pdo_pgsql' === $driver) {
                yield $name => [$parameters, $driver, empty($parameters['db_port']) ? '5432' : $port, $charset];
            }
        }
    }

    public function testAcceptsAvailableSupportedPdoDrivers(): void
    {
        if ([] === DoctrineStep::getDrivers()) {
            $this->markTestSkipped('A supported PDO driver is required.');
        }

        $configurator = $this->createStub(Configurator::class);
        $configurator->method('getParameters')->willReturn([]);
        $step = new DoctrineStep($configurator);

        $this->assertSame([], $step->checkRequirements());
    }
}
