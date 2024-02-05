<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CommandListEvent extends Event
{
    /**
     * @var array
     */
    protected $commands = [];

    /**
     * Returns the list of currently stored commands.
     *
     * @return mixed
     */
    public function getCommands()
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
