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

class ImportMappingEvent extends Event
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
     * @var array
     */
    private $fields;

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

    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
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

    /**
     * @param bool $objectSupported
     */
    public function setObjectIsSupported($objectSupported)
    {
        $this->objectSupported = $objectSupported;
    }
}
