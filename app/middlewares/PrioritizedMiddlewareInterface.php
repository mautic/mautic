<?php

namespace Mautic\Middleware;

interface PrioritizedMiddlewareInterface
{
    /**
     * Get the middleware's priority.
     *
     * @return int
     */
    public function getPriority();
}
