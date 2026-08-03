<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class CommandListEvent extends Event
{
    private array $commands = [];

    /**
     * Returns the list of currently stored commands.
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Add an array of search commands.
     *
     * @param string $header   String name for section header
     * @param array  $commands Array of commands supported by the repository
     */
    public function addCommands($header, array $commands): void
    {
        $this->commands[$header] = $commands;
    }
}
