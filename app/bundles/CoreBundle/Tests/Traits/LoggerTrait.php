<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Traits;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

trait LoggerTrait
{
    /**
     * @var ?HandlerInterface[]
     */
    private ?array $originalHandlers = null;
    private Logger $logger;
    private TestHandler $testHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger           = self::getContainer()->get('monolog.logger.mautic');
        $this->originalHandlers = $this->logger->getHandlers();
        $this->logger->setHandlers([$this->testHandler = new TestHandler()]);
    }

    protected function beforeTearDown(): void
    {
        $this->testHandler->clear();

        if (null !== $this->originalHandlers) {
            $this->logger->setHandlers($this->originalHandlers);
        }
    }
}
