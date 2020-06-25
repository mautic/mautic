<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\EventListener\MigrationCommandSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommandSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|VersionProviderInterface
     */
    private $versionProvider;

    /**
     * @var MockObject|GeneratedColumnsProviderInterface
     */
    private $generatedColumnsProvider;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var MockObject|ConsoleCommandEvent
     */
    private $event;

    /**
     * @var MockObject|Command
     */
    private $command;

    /**
     * @var MockObject|MySqlSchemaManager
     */
    private $schemaManager;

    /**
     * @var MockObject|OutputInterface
     */
    private $output;

    /**
     * @var GeneratedColumns
     */
    private $generatedColumns;

    /**
     * @var MigrationCommandSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->versionProvider          = $this->createMock(VersionProviderInterface::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->connection               = $this->createMock(Connection::class);
        $this->event                    = $this->createMock(ConsoleCommandEvent::class);
        $this->command                  = $this->createMock(Command::class);
        $this->output                   = $this->createMock(OutputInterface::class);
        $this->schemaManager            = $this->createMock(MySqlSchemaManager::class);
        $this->generatedColumns         = new GeneratedColumns();
        $this->subscriber               = new MigrationCommandSubscriber(
            $this->versionProvider,
            $this->generatedColumnsProvider,
            $this->connection
        );

        $this->event->method('getCommand')->willReturn($this->command);
        $this->event->method('getOutput')->willReturn($this->output);
        $this->connection->method('getSchemaManager')->willReturn($this->schemaManager);
        $this->generatedColumns->add(new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important'));
    }

    public function testAddGeneratedColumnsWillRunOnlyForMigrationCommand()
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('mautic:segments:update');

        $this->generatedColumnsProvider->expects($this->never())
            ->method('generatedColumnsAreSupported');

        $this->subscriber->addGeneratedColumns($this->event);
    }

    public function testAddGeneratedColumnsWhenNotSupported()
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

    public function testAddGeneratedColumnsWhenExistsAlready()
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
            ->willReturn(['generated_hit_date' => new \StdClass()]);

        $this->connection->expects($this->never())
            ->method('query');

        $this->subscriber->addGeneratedColumns($this->event);
    }

    public function testAddGeneratedColumnsWhenDoesNotExist()
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
            ->willReturn(['id' => new \StdClass()]);

        $this->connection->expects($this->once())
            ->method('query');

        $this->subscriber->addGeneratedColumns($this->event);
    }
}
