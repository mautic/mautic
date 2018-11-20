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

    /**
     * @param CallbackInterface $handler
     */
    public function registerHandler(CallbackInterface $handler)
    {
        $this->handlers[$handler->getCallbackPath()] = $handler;
    }

    /**
     * @param $callbackPath
     *
     * @return CallbackInterface
     * @throws CallbackHandlerNotFound
     */
    public function getHandler($callbackPath)
    {
        if (!isset($this->handlers[$callbackPath])) {
            throw new CallbackHandlerNotFound("$callbackPath has not been registered");
        }

        return $this->handlers[$callbackPath];
    }
}