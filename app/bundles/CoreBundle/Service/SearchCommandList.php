<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Event\CommandListEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SearchCommandList implements SearchCommandListInterface
{
    /**
     * @var mixed[]
     */
    private array $searchCommands = [];

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getList(): array
    {
        if ([] !== $this->searchCommands) {
            return $this->searchCommands;
        }

        $event = new CommandListEvent();
        $this->dispatcher->dispatch($event);

        return $this->searchCommands = $event->getCommands();
    }
}
