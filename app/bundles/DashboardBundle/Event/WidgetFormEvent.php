<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DashboardBundle\Entity\Widget;

final class WidgetFormEvent extends CommonEvent
{
    private $form;

    private $type;

    /**
     * Set the widget type.
     *
     * @param string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * Get the widget type.
     *
     * @return string
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
    public function setForm($form): void
    {
        $this->form = $form;
    }

    /**
     * Returns the widget edit form.
     */
    public function getForm()
    {
        return $this->form;
    }
}
