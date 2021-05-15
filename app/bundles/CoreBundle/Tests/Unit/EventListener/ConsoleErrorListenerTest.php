<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\ConsoleErrorListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleErrorListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|InputInterface
     */
    private $input;

    /**
     * @var MockObject|OutputInterface
     */
    private $output;

    private ?ConsoleErrorListener $listener;

    protected function setUp(): void
    {
        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->input    = $this->createMock(InputInterface::class);
        $this->output   = $this->createMock(OutputInterface::class);

        $this->listener = new ConsoleErrorListener($this->logger);
    }

    /**
     * Should not throw an error when command is null.
     */
    public function testConsoleErrorWithNullCommand()
    {
        $event = new ConsoleErrorEvent(
            $this->input,
            $this->output,
            new \Exception('Example exception'),
            null
        );

        $this->logger->expects($this->once())
            ->method('notice');

        $this->listener->onConsoleError($event);
    }
}
