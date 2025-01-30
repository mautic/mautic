<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Symfony\Component\HttpFoundation\Request;

class CustomButtonEvent extends AbstractCustomRequestEvent
{
    /**
     * @var array
     */
    protected $buttons = [];

    public function __construct(
        protected $location,
        Request $request,
        array $buttons = [],
        protected $item = null,
    ) {
        parent::__construct($request);

        foreach ($buttons as $button) {
            $this->buttons[$this->generateButtonKey($button)] = $button;
        }
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return array
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Add an array of buttons.
     *
     * @return $this
     */
    public function addButtons(array $buttons, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        foreach ($buttons as $button) {
            if (!isset($button['priority'])) {
                $button['priority'] = 0;
            }

            $this->buttons[$this->generateButtonKey($button)] = $button;
        }

        return $this;
    }

    /**
     * Add a single button.
     *
     * @param string|null $location
     * @param string|null $route
     *
     * @return $this
     */
    public function addButton(array $button, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        if (!isset($button['priority'])) {
            $button['priority'] = 0;
        }

        $this->buttons[$this->generateButtonKey($button)] = $button;

        return $this;
    }

    public function removeButton($button): void
    {
        $buttonKey = $this->generateButtonKey($button);
        if (isset($this->buttons[$buttonKey])) {
            unset($this->buttons[$buttonKey]);
        }
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    public function checkLocationContext($location): bool
    {
        if (null !== $location) {
            if ((is_array($location) && !in_array($this->location, $location)) || (is_string($location) && $location !== $this->location)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a button ID that can be overridden by other plugins.
     */
    protected function generateButtonKey($button): string
    {
        $buttonKey = '';
        if (!empty($button['btnText'])) {
            $buttonKey .= $button['btnText'];
        } elseif (isset($button['confirm'])) {
            if (!empty($button['confirm']['btnText'])) {
                $buttonKey .= $button['confirm']['btnText'];
            }

            if (!empty($button['confirm']['template'])) {
                $buttonKey .= $button['confirm']['template'];
            }

            if (!empty($button['confirm']['iconClass'])) {
                $buttonKey .= $button['confirm']['iconClass'];
            }
        }

        if (!empty($button['iconClass'])) {
            $buttonKey .= $button['iconClass'];
        }

        // Ensure buttons aren't overwritten unintentionally
        if (empty($buttonKey)) {
            $buttonKey = uniqid(time());
        }

        if (ButtonHelper::LOCATION_NAVBAR !== $this->location) {
            // Include the request
            [$currentRoute, $routeParams] = $this->getRoute(true);

            $buttonKey .= $currentRoute;

            foreach ($routeParams as $paramKey => $paramValue) {
                $buttonKey .= $paramKey.$paramValue;
            }
        }

        return $buttonKey;
    }
}
