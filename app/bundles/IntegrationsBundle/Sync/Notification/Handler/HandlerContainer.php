<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Handler;

use Mautic\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class HandlerContainer
{
    private array $handlers = [];

    /**
     * @param iterable<HandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('mautic.sync.notification_handler')]
        iterable $handlers = [],
    ) {
        foreach ($handlers as $handler) {
            $this->registerHandler($handler);
        }
    }

    private function registerHandler(HandlerInterface $handler): void
    {
        if (!isset($this->handlers[$handler->getIntegration()])) {
            $this->handlers[$handler->getIntegration()] = [];
        }

        $this->handlers[$handler->getIntegration()][$handler->getSupportedObject()] = $handler;
    }

    /**
     * @return HandlerInterface
     *
     * @throws HandlerNotSupportedException
     */
    public function getHandler(string $integration, string $object)
    {
        if (!isset($this->handlers[$integration])) {
            throw new HandlerNotSupportedException("{$integration} does not have any registered handlers");
        }

        if (!isset($this->handlers[$integration][$object])) {
            throw new HandlerNotSupportedException("{$integration} does not have any registered handlers for the object {$object}");
        }

        return $this->handlers[$integration][$object];
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers(): array
    {
        return array_reduce($this->handlers, array_merge(...), []);
    }
}
