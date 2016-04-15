<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * Class WidgetTypeListEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class WidgetTypeListEvent extends CommonEvent
{
    /**
     * @var array $widgetTypes
     */
    protected $widgetTypes = array();

    /**
     * @var Translator $translator
     */
    protected $translator;

    /**
     * @var CorePermissions $security
     */
    protected $security = null;

    /**
     * Adds a new widget type to the widget types list
     *
     * @param  string $widgetType
     * @param  string $bundle name (widget category)
     */
    public function addType($widgetType, $bundle = 'others')
    {
        $bundle = 'mautic.' . $bundle . '.dashboard.widgets';
        $widgetTypeName = 'mautic.widget.' . $widgetType;

        if ($this->translator) {
            $bundle = $this->translator->trans($bundle);
            $widgetTypeName = $this->translator->trans($widgetTypeName);
        }

        if (!isset($this->widgetTypes[$bundle])) {
            $this->widgetTypes[$bundle] = array();
        }

        $this->widgetTypes[$bundle][$widgetType] = $widgetTypeName;
    }

    /**
     * Set translator if you want the strings to be translated
     *
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set security object to check the perimissions
     *
     * @param CorePermissions $security
     */
    public function setSecurity(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * Check if the user has permission to see the widgets
     *
     * @param array $permissions
     *
     * @return boolean
     */
    public function hasPermissions(array $permissions)
    {
        if (!$this->security) return true;
        $perm = $this->security->isGranted($permissions, "RETURN_ARRAY");
        return !in_array(false, $perm);
    }

    /**
     * Returns the array of widget types
     *
     * @return array $widgetTypes
     */
    public function getTypes()
    {
        return $this->widgetTypes;
    }
}
