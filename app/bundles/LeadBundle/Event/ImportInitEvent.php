<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ImportInitEvent extends Event
{
    /**
     * @var string
     */
    private $routeObjectName;

    /**
     * @var bool
     */
    private $objectSupported = false;

    /**
     * @var string
     */
    private $objectSingular;

    /**
     * Object name for humans. Will go through translator.
     *
     * @var string
     */
    private $objectName;

    /**
     * @var string
     */
    private $activeLink;

    /**
     * @param string $routeObjectName
     */
    public function __construct($routeObjectName)
    {
        $this->routeObjectName = $routeObjectName;
    }

    /**
     * @return string
     */
    public function getRouteObjectName()
    {
        return $this->routeObjectName;
    }

    /**
     * @return bool
     */
    public function objectIsSupported()
    {
        return $this->objectSupported;
    }

    /**
     * @param string $objectSingular
     */
    public function setObjectSingular($objectSingular)
    {
        $this->objectSingular = $objectSingular;
    }

    /**
     * @return string
     */
    public function getObjectSingular()
    {
        return $this->objectSingular;
    }

    /**
     * @param string $objectName
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * @param string $activeLink
     */
    public function setActiveLink($activeLink)
    {
        $this->activeLink = $activeLink;
    }

    /**
     * @return string
     */
    public function getActiveLink()
    {
        return $this->activeLink;
    }

    /**
     * Check if the import is for said route object and notes if the object exist.
     *
     * @param string $routeObject
     *
     * @return bool
     */
    public function importIsForRouteObject($routeObject)
    {
        if ($this->getRouteObjectName() === $routeObject) {
            $this->objectSupported = true;

            return true;
        }

        return false;
    }
}
