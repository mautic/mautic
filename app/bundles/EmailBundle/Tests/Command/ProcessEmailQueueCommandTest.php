<?php

namespace Mautic\EmailBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Command\ProcessEmailQueueCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SwiftmailerBundle\Command\SendEmailCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessEmailQueueCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var MockObject&PathsHelper
     */
    private $pathsHelper;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject&\Swift_Transport
     */
    private $transport;

    /**
     * @var MockObject&Application
     */
    private $application;

    /**
     * @var MockObject&SendEmailCommand
     */
    private $subCommand;

    /**
     * @var ProcessEmailQueueCommand
     */
    private $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->transport            = $this->createMock(\Swift_Transport::class);
        $this->application          = $this->createMock(Application::class);
        $this->subCommand           = $this->createMock(SendEmailCommand::class);

        $this->application->method('getHelperSet')
            ->willReturn($this->createMock(HelperSet::class));

        $inputDefinition = $this->createMock(InputDefinition::class);

        $this->application->method('getDefinition')
            ->willReturn($inputDefinition);

        $inputDefinition->method('getOptions')
          ->will($this->returnValue([
              new InputOption('--quiet', '-q', InputOption::VALUE_OPTIONAL, 'Do not output any message'),
          ]));

        $this->command = new ProcessEmailQueueCommand(
            $this->transport,
            $this->dispatcher,
            $this->coreParametersHelper,
            $this->pathsHelper
        );
        $this->command->setApplication($this->application);
    }

    public function testCommandWhenQueueIsDisabled()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertSame("Mautic is not set to queue email.\n", $output->fetch());
    }

    /**
     * Ensure this error won't happen:.
     *
     * Error: Swift_Mime_SimpleMimeEntity::_getHeaderFieldModel(): The script tried to
     * execute a method or access a property of an incomplete object. Please ensure
     * that the class definition "Swift_Mime_SimpleHeaderSet" of the object you are
     * trying to operate on was loaded _before_ unserialize() gets called or provide
     * an autoloader to load the class definition
     */
    public function testCommandWhenQueueIsEnabled()
    {
        $tryAgainMessageFile    = 'swift_message.tryagain';
        $tmpSpoolDir            = sys_get_temp_dir().'/mauticSpoolTestDir';
        $tryAgainMessage        = __DIR__.'/../Data/SpoolSample/'.$tryAgainMessageFile;
        $tmpTryAgainMessageFile = $tmpSpoolDir.'/'.$tryAgainMessageFile;
        if (!file_exists($tmpSpoolDir)) {
            mkdir($tmpSpoolDir, 0777, true);
        }
        copy($tryAgainMessage, $tmpTryAgainMessageFile);

        $this->coreParametersHelper->method('get')
            ->withConsecutive(
                ['mailer_spool_type'],
                ['mautic.mailer_spool_path'],
                ['mautic.mailer_spool_msg_limit']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                $tmpSpoolDir,
                10
            );

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (\Swift_Message $message) {
                // This triggers the error this test was created for.
                $message->getReturnPath();

                return true;
            }));

        $this->application->expects($this->once())
            ->method('find')
            ->with('swiftmailer:spool:send')
            ->willReturn($this->subCommand);

        $input  = new ArrayInput(['--bypass-locking' => true, '--clear-timeout' => 10]);
        $output = new BufferedOutput();
        $this->assertSame(0, $this->command->run($input, $output));

        // The file is deleted after successful send.
        $this->assertFalse(file_exists($tmpTryAgainMessageFile));

        // Cleanup.
        unset($tmpSpoolDir);
    }

    public function testCommandWithQuietFlag(): void
    {
        $this->coreParametersHelper->expects($this->any())
          ->method('get')
          ->will($this->returnValueMap([['mailer_spool_type', null, 'file']]));

        $this->subCommand->expects($this->exactly(2))
          ->method('run')
          ->willReturnCallback(function (InputInterface $input, OutputInterface $output) {
              $output->writeln('0 messages send');

              return 0;
          });

        $this->application->expects($this->exactly(2))
          ->method('find')
          ->with('swiftmailer:spool:send')
          ->willReturn($this->subCommand);

        // test non-quiet mode
        $input  = new ArrayInput(['--bypass-locking' => true, '--clear-timeout' => 10, '--quiet' => 0]);
        $output = new BufferedOutput();
        $this->assertSame(0, $this->command->run($input, $output));
        $this->assertSame("0 messages send\n", $output->fetch());

        // test quiet mode
        $input  = new ArrayInput(['--bypass-locking' => true, '--clear-timeout' => 10, '--quiet' => 1]);
        $output = new BufferedOutput();
        $this->assertSame(0, $this->command->run($input, $output));
        $this->assertSame('', $output->fetch());
    }
}
