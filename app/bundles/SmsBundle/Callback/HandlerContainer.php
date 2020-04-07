<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback;

use Mautic\SmsBundle\Exception\CallbackHandlerNotFound;

class HandlerContainer
{
    /**
     * @var CallbackInterface[]
     */
    private $handlers;

    public function registerHandler(CallbackInterface $handler)
    {
        $this->handlers[$handler->getTransportName()] = $handler;
    }

    /**
     * @param $transportName
     *
     * @return CallbackInterface
     *
     * @throws CallbackHandlerNotFound
     */
    public function getHandler($transportName)
    {
        if (!isset($this->handlers[$transportName])) {
            throw new CallbackHandlerNotFound("$transportName has not been registered");
        }

        return $this->handlers[$transportName];
    }
}
