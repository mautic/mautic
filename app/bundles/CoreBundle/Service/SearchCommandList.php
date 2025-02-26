<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CommandListEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SearchCommandList implements SearchCommandListInterface
{
    /**
     * @var mixed[]
     */
    private array $searchCommands = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getList(): array
    {
        if (!empty($this->searchCommands)) {
            return $this->searchCommands;
        }

        $event = new CommandListEvent();
        $this->dispatcher->dispatch($event, CoreEvents::BUILD_COMMAND_LIST);

        return $this->searchCommands = $event->getCommands();
    }
}
