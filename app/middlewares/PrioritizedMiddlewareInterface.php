<?php

declare(strict_types=1);

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
