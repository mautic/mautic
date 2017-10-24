<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Message;

interface InterfaceProcessor
{
    /**
     * Set the message.
     *
     * @param Message $message
     *
     * @return InterfaceProcessor
     */
    public function setMessage(Message $message);

    /**
     * Process the message.
     *
     * @return bool
     */
    public function process();
}
