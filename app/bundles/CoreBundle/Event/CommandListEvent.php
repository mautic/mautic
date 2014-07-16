<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CommandListEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class CommandListEvent extends Event
{

    /**
     * @var
     */
    protected $commands = array();

    /**
     * Returns the list of currently stored commands
     *
     * @return mixed
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Add an array of search commands
     *
     * @param       $header  String name for section header
     * @param array $commands Array of commands supported by the repository
     */
    public function addCommands($header, array $commands)
    {
        $this->commands[$header] = $commands;
    }
}
