<?php

namespace Mautic\FormBundle\ProgressiveProfiling;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

class DisplayCounter
{
    private int $displayedFields = 0;

    private int $alreadyAlwaysDisplayed = 0;

    public function __construct(
        private Form $form,
    ) {
    }

    public function increaseDisplayedFields(): void
    {
        ++$this->displayedFields;
    }

    public function getDisplayFields(): int
    {
        return $this->displayedFields;
    }

    public function increaseAlreadyAlwaysDisplayed(): void
    {
        ++$this->alreadyAlwaysDisplayed;
    }

    public function getAlreadyAlwaysDisplayed(): int
    {
        return $this->alreadyAlwaysDisplayed;
    }

    public function getAlwaysDisplayFields(): int
    {
        $i= 0;
        /** @var Field $field */
        foreach ($this->form->getFields()->toArray() as $field) {
            if ($field->isAlwaysDisplay()) {
                ++$i;
            }
        }

        return $i;
    }
}
