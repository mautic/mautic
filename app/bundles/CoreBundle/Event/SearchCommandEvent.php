<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SearchCommandEvent extends Event
{
    use SearchEventTrait;

    /**
     * @param string[] $commands
     */
    public function __construct(private array $commands, private string $context)
    {
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param string[] $commands
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    public function addCommand(string $command): void
    {
        $this->commands[] = $command;
    }
}
