<?php

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
