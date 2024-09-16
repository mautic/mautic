<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Test\IsolatedTestTrait;
use Mautic\IntegrationsBundle\Command\SyncCommand;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * This test must run in a separate process because it sets the global constant
 * MAUTIC_INTEGRATION_SYNC_IN_PROGRESS which breaks other tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SyncCommandTest extends TestCase
{
    use IsolatedTestTrait;

    private const INTEGRATION_NAME = 'Test';

    /**
     * @var SyncServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $syncService;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->syncService = $this->createMock(SyncServiceInterface::class);
        $application       = new Application();

        $application->add(new SyncCommand($this->syncService));

        // env is global option. Must be defined.
        $application->getDefinition()->addOption(
            new InputOption(
                '--env',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'The environment to operate in.',
                'DEV'
            )
        );

        $this->commandTester = new CommandTester(
            $application->find(SyncCommand::NAME)
        );
    }

    public function testExecuteWithoutIntetrationName(): void
    {
        $this->assertSame(1, $this->commandTester->execute([]));
    }

    public function testExecuteWithSomeOptions(): void
    {
        $this->syncService->expects($this->once())
            ->method('processIntegrationSync')
            ->with($this->callback(function (InputOptionsDAO $inputOptionsDAO) {
                $this->assertSame(self::INTEGRATION_NAME, $inputOptionsDAO->getIntegration());
                $this->assertSame(['123', '345'], $inputOptionsDAO->getMauticObjectIds()->getObjectIdsFor(Contact::NAME));
                $this->assertNull($inputOptionsDAO->getIntegrationObjectIds());
                $this->assertTrue($inputOptionsDAO->pullIsEnabled());
                $this->assertFalse($inputOptionsDAO->pushIsEnabled());

                return true;
            }));

        $code = $this->commandTester->execute([
            'integration'        => self::INTEGRATION_NAME,
            '--disable-push'     => true,
            '--mautic-object-id' => ['contact:123', 'contact:345'],
        ]);

        $this->assertSame(0, $code);
    }

    public function testExecuteWhenSyncThrowsException(): void
    {
        $this->syncService->expects($this->once())
            ->method('processIntegrationSync')
            ->with($this->callback(function (InputOptionsDAO $inputOptionsDAO) {
                $this->assertSame(self::INTEGRATION_NAME, $inputOptionsDAO->getIntegration());

                return true;
            }))
            ->will($this->throwException(new \Exception()));

        $code = $this->commandTester->execute(['integration' => self::INTEGRATION_NAME]);

        $this->assertSame(1, $code);
    }
}
