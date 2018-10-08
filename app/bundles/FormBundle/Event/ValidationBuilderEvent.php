<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\Form\Form;

/**
 * Class ValidationBuilderEvent.
 */
class ValidationBuilderEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $validators;

    /**
     * @var array
     */
    private $formField;

    /**
     * ValidationBuilderEvent constructor.
     *
     * @param array $formField
     */
    public function __construct(array $formField)
    {
        $this->validators = [];
        $this->formField  = $formField;
    }

    /**
     * @return array
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * @param array $validators
     */
    public function setValidator($validator)
    {
        $this->validators[] = $validator;
    }

    /**
     * @return array
     */
    public function getFormField()
    {
        return $this->formField;
    }

    /**
     * @param Form $form
     */
    public function setValidatorsToForm(Form $form)
    {
        if (!empty($this->validators)) {
            $validationData = (isset($form->getData()['validation'])) ? $form->getData()['validation'] : [];
            foreach ($this->validators as $validator) {
                $form->add(
                    'validation',
                    $validator,
                    [
                        'label' => false,
                        'data'  => $validationData,
                    ]
                );
            }
        }
    }
}
