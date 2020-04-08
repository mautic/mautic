<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\Notification\Handler;

use Mautic\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;

class HandlerContainer
{
    /**
     * @var array
     */
    private $handlers = [];

    public function registerHandler(HandlerInterface $handler): void
    {
        if (!isset($this->handlers[$handler->getIntegration()])) {
            $this->handlers[$handler->getIntegration()] = [];
        }

        $this->handlers[$handler->getIntegration()][$handler->getSupportedObject()] = $handler;
    }

    /**
     * @return HandlerInterface
     *
     * @throws HandlerNotSupportedException
     */
    public function getHandler(string $integration, string $object)
    {
        if (!isset($this->handlers[$integration])) {
            throw new HandlerNotSupportedException("$integration does not have any registered handlers");
        }

        if (!isset($this->handlers[$integration][$object])) {
            throw new HandlerNotSupportedException("$integration does not have any registered handlers for the object $object");
        }

        return $this->handlers[$integration][$object];
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return array_reduce($this->handlers, function ($accumulator, $integrationHandlers) {
            return array_merge($accumulator, $integrationHandlers);
        }, []);
    }
}
