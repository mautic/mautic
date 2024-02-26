<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\EventListener\MigrationCommandSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommandSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|VersionProviderInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $versionProvider;

    /**
     * @var MockObject|GeneratedColumnsProviderInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $generatedColumnsProvider;

    /**
     * @var MockObject|Connection
     */
    private \PHPUnit\Framework\MockObject\MockObject $connection;

    private \Symfony\Component\Console\Event\ConsoleCommandEvent $event;

    /**
     * @var MockObject|Command
     */
    private \PHPUnit\Framework\MockObject\MockObject $command;

    /**
     * @var MockObject|MySqlSchemaManager
     */
    private \PHPUnit\Framework\MockObject\MockObject $schemaManager;

    /**
     * @var MockObject|OutputInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $output;

    /**
     * @var GeneratedColumns<GeneratedColumn>
     */
    private \Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns $generatedColumns;

    private \Mautic\CoreBundle\EventListener\MigrationCommandSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionProvider          = $this->createMock(VersionProviderInterface::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->connection               = $this->createMock(Connection::class);
        $this->command                  = $this->createMock(Command::class);
        $this->output                   = $this->createMock(OutputInterface::class);
        $this->schemaManager            = $this->createMock(MySqlSchemaManager::class);
        $this->generatedColumns         = new GeneratedColumns();
        $this->subscriber               = new MigrationCommandSubscriber(
            $this->versionProvider,
            $this->generatedColumnsProvider,
            $this->connection
        );

        $input = $this->createMock(InputInterface::class);

        $this->event = new ConsoleCommandEvent($this->command, $input, $this->output);

        $this->connection->method('createSchemaManager')->willReturn($this->schemaManager);
        $this->generatedColumns->add(new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important'));
    }

    public function testAddGeneratedColumnsWillRunOnlyForMigrationCommand(): void
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('mautic:segments:update');

        $this->generatedColumnsProvider->expects($this->never())
            ->method('generatedColumnsAreSupported');

        $this->subscriber->addGeneratedColumns($this->event);
    }

    public function testAddGeneratedColumnsWhenNotSupported(): void
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('doctrine:migrations:migrate');

        $this->generatedColumnsProvider->expects($this->once())
            ->method('generatedColumnsAreSupported')
            ->willReturn(false);

        $this->generatedColumnsProvider->expects($this->never())
            ->method('getGeneratedColumns');

        $this->subscriber->addGeneratedColumns($this->event);
    }

    public function testAddGeneratedColumnsWhenExistsAlready(): void
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('doctrine:migrations:migrate');

        $this->generatedColumnsProvider->expects($this->once())
            ->method('generatedColumnsAreSupported')
            ->willReturn(true);

        $this->generatedColumnsProvider->expects($this->once())
            ->method('getGeneratedColumns')
            ->willReturn($this->generatedColumns);

        $this->schemaManager->expects($this->once())
            ->method('listTableColumns')
            ->willReturn(['generated_hit_date' => new \stdClass()]);

        $this->connection->expects($this->never())
            ->method('executeQuery');

        $this->subscriber->addGeneratedColumns($this->event);
    }

    public function testAddGeneratedColumnsWhenDoesNotExist(): void
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('doctrine:migrations:migrate');

        $this->generatedColumnsProvider->expects($this->once())
            ->method('generatedColumnsAreSupported')
            ->willReturn(true);

        $this->generatedColumnsProvider->expects($this->once())
            ->method('getGeneratedColumns')
            ->willReturn($this->generatedColumns);

        $this->schemaManager->expects($this->once())
            ->method('listTableColumns')
            ->willReturn(['id' => new \stdClass()]);

        $this->connection->expects($this->once())
            ->method('executeQuery');

        $this->subscriber->addGeneratedColumns($this->event);
    }
}
