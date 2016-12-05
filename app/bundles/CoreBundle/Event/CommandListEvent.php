<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CommandListEvent.
 */
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
    public function addCommands($header, array $commands)
    {
        $this->commands[$header] = $commands;
    }
}
