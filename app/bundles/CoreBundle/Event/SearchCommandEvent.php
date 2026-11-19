<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

final class SearchCommandEvent extends AbstractSearchEvent
{
    /**
     * @param string[] $commands
     */
    public function __construct(private array $commands, protected string $context)
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
