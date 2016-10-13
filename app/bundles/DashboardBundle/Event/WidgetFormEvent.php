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
use Mautic\DashboardBundle\Entity\Widget;

/**
 * Class WidgetFormEvent.
 */
class WidgetFormEvent extends CommonEvent
{
    protected $form;
    protected $type;

    /**
     * Set the widget type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the widget type.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the widget form.
     *
     * @param string $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Returns the widget edit form.
     *
     * @param string $form
     */
    public function getForm()
    {
        return $this->form;
    }
}
