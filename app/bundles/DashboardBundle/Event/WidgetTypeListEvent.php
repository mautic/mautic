<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WidgetTypeListEvent.
 */
class WidgetTypeListEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $widgetTypes = [];

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CorePermissions
     */
    protected $security = null;

    /**
     * Adds a new widget type to the widget types list.
     *
     * @param string $widgetType
     * @param string $bundle     name (widget category)
     */
    public function addType($widgetType, $bundle = 'others')
    {
        $bundle         = 'mautic.'.$bundle.'.dashboard.widgets';
        $widgetTypeName = 'mautic.widget.'.$widgetType;

        if ($this->translator) {
            $bundle         = $this->translator->trans($bundle);
            $widgetTypeName = $this->translator->trans($widgetTypeName);
        }

        if (!isset($this->widgetTypes[$bundle])) {
            $this->widgetTypes[$bundle] = [];
        }

        $this->widgetTypes[$bundle][$widgetType] = $widgetTypeName;
    }

    /**
     * Set translator if you want the strings to be translated.
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set security object to check the perimissions.
     *
     * @param CorePermissions $security
     */
    public function setSecurity(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * Check if the user has permission to see the widgets.
     *
     * @param array $permissions
     *
     * @return bool
     */
    public function hasPermissions(array $permissions)
    {
        if (!$this->security) {
            return true;
        }
        $perm = $this->security->isGranted($permissions, 'RETURN_ARRAY');

        return !in_array(false, $perm);
    }

    /**
     * Returns the array of widget types.
     *
     * @return array $widgetTypes
     */
    public function getTypes()
    {
        return $this->widgetTypes;
    }
}
