<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\ConsoleErrorListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleErrorListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $logger;

    private ConsoleErrorListener $listener;

    protected function setUp(): void
    {
        $this->logger   = $this->createMock(LoggerInterface::class);

        $this->listener = new ConsoleErrorListener($this->logger);
    }

    /**
     * Should not throw an error when command is null.
     */
    public function testConsoleErrorWithNullCommand(): void
    {
        $event = new ConsoleErrorEvent(
            $this->createStub(InputInterface::class),
            $this->createStub(OutputInterface::class),
            new \Exception('Example exception')
        );

        $this->logger->expects($this->once())
            ->method('error');

        $this->listener->onConsoleError($event);
    }
}
