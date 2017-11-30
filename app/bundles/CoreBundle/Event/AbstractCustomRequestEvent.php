<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCustomRequestEvent extends Event
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var
     */
    protected $route;

    /**
     * @var array
     */
    protected $routeParams = [];

    /**
     * AbstractCustomRequestEvent constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request = null)
    {
        if ($request) {
            $this->request = ($request->isXmlHttpRequest() && $request->query->has('request')) ? $request->query->get('request') : $request;
            if ($this->request->attributes->has('ajaxRoute')) {
                $ajaxRoute         = $this->request->attributes->get('ajaxRoute');
                $this->route       = $ajaxRoute['_route'];
                $this->routeParams = $ajaxRoute['_route_params'];
            } else {
                $this->route       = $this->request->attributes->get('_route');
                $this->routeParams = $this->request->attributes->get('_route_params');
            }

            if (null === $this->routeParams) {
                $this->routeParams = [];
            }
        }
    }

    /**
     * @return request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get Symfony route name for the current view.
     *
     * @param bool $withParams
     *
     * @return array|mixed
     */
    public function getRoute($withParams = false)
    {
        return ($withParams) ? [$this->route, $this->routeParams] : $this->route;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    public function checkRouteContext($route)
    {
        if (null == $this->request) {
            return false;
        }

        if (null !== $route) {
            list($currentRoute, $routeParams) = $this->getRoute(true);
            $givenRoute                       = $route;
            $givenRouteParams                 = [];
            if (is_array($route)) {
                list($givenRoute, $givenRouteParams) = $route;
            }

            if ($givenRoute !== $currentRoute) {
                return false;
            }

            foreach ($givenRouteParams as $param => $value) {
                if (!isset($routeParams[$param]) || $value !== $routeParams[$param]) {
                    return false;
                }
            }
        }

        return true;
    }
}
