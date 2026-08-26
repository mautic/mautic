<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Form;

final class FormEvent extends CommonEvent
{
    public function __construct(Form &$form, bool $isNew = false)
    {
        $this->entity = &$form;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Form entity.
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->entity;
    }

    /**
     * Sets the Form entity.
     */
    public function setForm(Form $form): void
    {
        $this->entity = $form;
    }
}
