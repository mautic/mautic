<?php

namespace Mautic\FormBundle\ProgressiveProfiling;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

class DisplayManager
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var array
     */
    private $viewOnlyFields;

    /**
     * @var DisplayCounter
     */
    private $displayCounter;

    public function __construct(Form $form, array $viewOnlyFields = [])
    {
        $this->form           = $form;
        $this->viewOnlyFields = $viewOnlyFields;
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

    /**
     * @return bool
     */
    private function shouldDisplayNotAlwaysDisplayField(Field $field): bool
    {
        $fields = $this->form->getFields()->toArray();
        foreach ($fields as $fieldFromArray) {
            /** @var Field $fieldFromArray */
            if (in_array($field->getType(), $this->viewOnlyFields)) {
                continue;
            }

            if ($field->getId() === $fieldFromArray->getId()) {
                if (($this->displayCounter->getDisplayFields() + ($this->displayCounter->getAlwaysDisplayFields() - $this->displayCounter->getAlreadyAlwaysDisplayed())) >= $this->form->getProgressiveProfilingLimit()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
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

    public function increaseDisplayedFields(Field $field)
    {
        if (!in_array($field->getType(), $this->viewOnlyFields)) {
            $this->displayCounter->increaseDisplayedFields();
        }
    }
}
