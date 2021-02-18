<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
