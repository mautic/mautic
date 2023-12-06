<?php

namespace Mautic\FormBundle\ProgressiveProfiling;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

class DisplayCounter
{
    /**
     * @var int
     */
    private $displayedFields = 0;

    /**
     * @var int
     */
    private $alreadyAlwaysDisplayed = 0;

    private \Mautic\FormBundle\Entity\Form $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    public function increaseDisplayedFields(): void
    {
        ++$this->displayedFields;
    }

    /**
     * @return int
     */
    public function getDisplayFields()
    {
        return $this->displayedFields;
    }

    public function increaseAlreadyAlwaysDisplayed(): void
    {
        ++$this->alreadyAlwaysDisplayed;
    }

    /**
     * @return int
     */
    public function getAlreadyAlwaysDisplayed()
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
