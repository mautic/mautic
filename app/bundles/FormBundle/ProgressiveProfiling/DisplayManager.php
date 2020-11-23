<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * DisplayManager constructor.
     */
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
    private function shouldDisplayNotAlwaysDisplayField(Field $field)
    {
        /** @var Field $fieldFromArray */
        $fields = $this->form->getFields()->toArray();
        foreach ($fields as $fieldFromArray) {
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
    public function useProgressiveProfilingLimit()
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
