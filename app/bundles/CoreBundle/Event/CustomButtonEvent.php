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
use Symfony\Component\Routing\Router;

class CustomButtonEvent extends Event
{
    /**
     * Button location requested.
     *
     * @var
     */
    protected $location;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $prependedButtons;

    /**
     * @var array
     */
    protected $appendedButtons;

    /**
     * Entity for list/view actions.
     *
     * @var mixed
     */
    protected $item;

    /**
     * CustomButtonEvent constructor.
     *
     * @param         $location
     * @param Request $request
     * @param Router  $router
     * @param array   $prependedButtons
     * @param array   $appendedButtons
     * @param null    $item
     */
    public function __construct($location, Request $request, array $prependedButtons = [], array $appendedButtons = [], $item = null)
    {
        $this->location         = $location;
        $this->prependedButtons = $prependedButtons;
        $this->appendedButtons  = $appendedButtons;
        $this->item             = $item;

        // The original request will be stored in the subrequest
        $this->request = ($request->isXmlHttpRequest() && $request->query->has('request')) ? $request->query->get('request') : $request;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
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
        return ($withParams) ? [$this->request->attributes->get('_route'), $this->request->attributes->get('_route_params')]
            : $this->request->attributes->get('_route');
    }

    /**
     * @return array
     */
    public function getPrependedButtons()
    {
        return $this->prependedButtons;
    }

    /**
     * @return array
     */
    public function getAppendedButtons()
    {
        return $this->appendedButtons;
    }

    /**
     * Append an array of buttons.
     *
     * @param array $buttons
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function appendButtons(array $buttons, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        $this->appendedButtons = array_merge($this->appendedButtons, $buttons);

        return $this;
    }

    /**
     * Prepend an array of buttons.
     *
     * @param array $buttons
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function prependButtons(array $buttons, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        $this->prependedButtons = array_merge($this->prependedButtons, $buttons);

        return $this;
    }

    /**
     * Append a single button.
     *
     * @param array $button
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function appendButton(array $button, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        $this->appendedButtons[] = $button;

        return $this;
    }

    /**
     * Prepend a single button.
     *
     * @param array $button
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function prependButton(array $button, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        $this->prependedButtons[] = $button;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param $location
     *
     * @return bool
     */
    public function checkLocationContext($location)
    {
        if (null !== $location) {
            if ((is_array($location) && !in_array($this->location, $location)) || (is_string($location) && $location !== $this->location)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    public function checkRouteContext($route)
    {
        if (null !== $route) {
            list($currentRoute, $routeParams) = $this->getRoute(true);

            $givenRoute       = $route;
            $givenRouteParams = [];
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
