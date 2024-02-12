<?php

namespace Mautic\FormBundle\ProgressiveProfiling;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

class DisplayManager
{
    private \Mautic\FormBundle\ProgressiveProfiling\DisplayCounter $displayCounter;

    public function __construct(
        private Form $form,
        private array $viewOnlyFields = []
    ) {
        $this->displayCounter = new DisplayCounter($form);
    }

    /**
     * @return bool
     */
    public function showForField(Field $field)
    {
        if (in_array($field->getType(), $this->viewOnlyFields)) {
            return true;
        }

        // Always Display field priority until hit limit
        if ($field->isAlwaysDisplay()) {
            if ($this->form->getProgressiveProfilingLimit() <= $this->displayCounter->getDisplayFields()) {
                return false;
            } else {
                $this->displayCounter->increaseAlreadyAlwaysDisplayed();

                return true;
            }
        }

        if ($this->shouldDisplayNotAlwaysDisplayField($field)) {
            return true;
        } else {
            return false;
        }
    }

    private function shouldDisplayNotAlwaysDisplayField(Field $field): bool
    {
        $fields = $this->form->getFields()->toArray();
        foreach ($fields as $fieldFromArray) {
            if (in_array($field->getType(), $this->viewOnlyFields)) {
                continue;
            }

            /** @var Field $fieldFromArray */
            if ($field->getId() === $fieldFromArray->getId()) {
                if (($this->displayCounter->getDisplayFields() + ($this->displayCounter->getAlwaysDisplayFields() - $this->displayCounter->getAlreadyAlwaysDisplayed())) >= $this->form->getProgressiveProfilingLimit()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function useProgressiveProfilingLimit(): bool
    {
        return '' != $this->form->getProgressiveProfilingLimit();
    }

    /**
     * @return DisplayCounter
     */
    public function getDisplayCounter()
    {
        return $this->displayCounter;
    }

    public function increaseDisplayedFields(Field $field): void
    {
        if (!in_array($field->getType(), $this->viewOnlyFields)) {
            $this->displayCounter->increaseDisplayedFields();
        }
    }
}
