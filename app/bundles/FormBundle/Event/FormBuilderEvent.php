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

use Mautic\CoreBundle\Event\ComponentValidationTrait;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;

/**
 * Class FormBuilderEvent.
 */
class FormBuilderEvent extends Event
{
    use ComponentValidationTrait;

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $validators = [];

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     */
    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds a submit action to the list of available actions.
     *
     * @param string $key    a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $action can contain the following keys:
     *                       $action = [
     *                       'group'              => (required) Label of the group to add this action to
     *                       'label'              => (required) what to display in the list
     *                       'eventName'          => (required) Event dispatched to execute action; it will receive a SubmissionEvent object
     *                       'formType'           => (required) name of the form type SERVICE for the action
     *                       'allowCampaignForm'  => (optional) true to allow this action for campaign forms; defaults to false
     *                       'description'        => (optional) short description of event
     *                       'template'           => (optional) template to use for the action's HTML in the form builder;
     *                       eg AcmeMyBundle:FormAction:theaction.html.php
     *                       'formTypeOptions'    => (optional) array of options to pass to formType
     *                       'formTheme'          => (optional  theme for custom form views
     *                       'validator'          => (deprecated) callback function to validate form results - use addValidator() instead
     *                       'callback'           => (deprecated) callback function that will be passed the results upon a form submit; use eventName instead
     *                       ]
     *
     * @throws \InvalidArgumentException
     */
    public function addSubmitAction($key, array $action)
    {
        if (array_key_exists($key, $this->actions)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['group', 'label', 'formType', ['eventName', 'callback', 'validator']],
            $action
        );

        $action['label'] = $this->translator->trans($action['label']);

        if (!isset($action['description'])) {
            $action['description'] = '';
        }

        $this->actions[$key] = $action;
    }

    /**
     * Get submit actions.
     *
     * @return array
     */
    public function getSubmitActions()
    {
        uasort(
            $this->actions,
            function ($a, $b) {
                return strnatcasecmp(
                    $a['label'],
                    $b['label']
                );
            }
        );

        return $this->actions;
    }

    /**
     * Get submit actions by groups.
     *
     * @return array
     */
    public function getSubmitActionGroups()
    {
        $actions = $this->getSubmitActions();
        $groups  = [];
        foreach ($actions as $key => $action) {
            $groups[$action['group']][$key] = $action;
        }

        return $groups;
    }

    /**
     * Adds a form field to the list of available fields in the form builder.
     *
     * @param string $key   unique identifier; it is recommended that it be namespaced i.e. leadbundle.myfield
     * @param array  $field can contain the following key/values
     *                      $field = [
     *                      'label'            => (required) what to display in the list
     *                      'formType'         => (required) name of the form type SERVICE for the field's property column
     *                      'template'         => (required) template to use for the field's HTML eg AcmeMyBundle:FormField:thefield.html.php
     *                      'formTypeOptions'  => (optional) array of options to pass to formType
     *                      'formTheme'        => (optional) theme for custom form view
     *                      'valueFilter'      => (optional) the filter to use to clean the input as supported by InputHelper or a callback;
     *                      should accept arguments FormField $field and $filteredValue
     *                      'valueConstraints' => (deprecated) callback to use to validate the value; use addValidator() instead
     *                      'builderOptions'   => (optional) array of options
     *                      [
     *                      'addHelpMessage'     => (bool) show help message inputs
     *                      'addShowLabel'       => (bool) show label input
     *                      'addDefaultValue'    => (bool) show default value input
     *                      'addLabelAttributes' => (bool) show label attribute input
     *                      'addInputAttributes' => (bool) show input attribute input
     *                      'addIsRequired'      => (bool) show is required toggle
     *                      ]
     *                      ]
     *
     * @throws \InvalidArgumentException
     */
    public function addFormField($key, array $field)
    {
        if (array_key_exists($key, $this->fields)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another field. Please use a different key.");
        }

        $callbacks = ['valueConstraints'];

        // Only validate valueFilter if it's not a InputHelper method
        if (isset($field['valueFilter'])
            && (!is_string($field['valueFilter'])
                || !is_callable(
                    ['\Mautic\CoreBundle\Helper\InputHelper', $field['valueFilter']]
                ))
        ) {
            $callbacks = ['valueFilter'];
        }

        $this->verifyComponent(['label', 'formType', 'template'], $field, $callbacks);

        $this->fields[$key] = $field;
    }

    /**
     * Get form fields.
     *
     * @return mixed
     */
    public function getFormFields()
    {
        return $this->fields;
    }

    /**
     * Add a field validator.
     *
     * @param       $key
     * @param array $validator
     *                         $validator = [
     *                         'eventName' => (required) Event name to dispatch to validate the form; it will recieve a ValidationEvent object
     *                         'fieldType' => (optional) Optional filter to validate only a specific type of field; otherwise every field
     *                         will be sent through the validation event
     *                         ]
     */
    public function addValidator($key, array $validator)
    {
        if (array_key_exists($key, $this->fields)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another validator. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(['eventName'], $validator);

        $this->validators[$key] = $validator;
    }

    /**
     * @param Form $form
     */
    public function addValidatorsToBuilder(Form $form)
    {
        if (!empty($this->validators)) {
            $validationData = (isset($form->getData()['validation'])) ? $form->getData()['validation'] : [];
            foreach ($this->validators as $validator) {
                if (isset($validator['formType']) && isset($validator['fieldType']) && $validator['fieldType'] == $form->getData()['type']) {
                    $form->add(
                        'validation',
                        $validator['formType'],
                        [
                            'label' => false,
                            'data'  => $validationData,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Returns validators organized by ['form' => [], 'fieldType' => [], ...
     *
     * @return array
     */
    public function getValidators()
    {
        // Organize by field
        $fieldValidators = [
            'form' => [],
        ];

        foreach ($this->validators as $validator) {
            if (isset($validator['fieldType'])) {
                if (!isset($fieldValidators[$validator['fieldType']])) {
                    $fieldValidators[$validator['fieldType']] = [];
                }

                $fieldValidators[$validator['fieldType']] = $validator['eventName'];
            } else {
                $fieldValidators['form'] = $validator['eventName'];
            }
        }

        return $fieldValidators;
    }
}
