<?php

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\ComponentValidationTrait;
use Mautic\CoreBundle\Exception\BadConfigurationException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormBuilderEvent extends Event
{
    use ComponentValidationTrait;

    private array $actions = [];

    private array $fields = [];

    private array $validators = [];

    public function __construct(
        private TranslatorInterface $translator,
    ) {
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
     *                       'template'           => (optional) template to use for the action's HTML in the form builder; eg AcmeMyBundle:FormAction:theaction.html.twig
     *                       'formTypeOptions'    => (optional) array of options to pass to formType
     *                       'formTheme'          => (optional  theme for custom form views
     *                       ]
     *
     * @throws BadConfigurationException
     */
    public function addSubmitAction(string $key, array $action): void
    {
        if (array_key_exists($key, $this->actions)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        // check for required keys and that given functions are callable
        $this->verifyComponent(
            ['group', 'label', 'formType', 'eventName'],
            $action
        );

        $action['label']       = $this->translator->trans($action['label']);
        $action['description'] ??= '';

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
            fn ($a, $b): int => strnatcasecmp(
                $a['label'],
                $b['label']
            )
        );

        return $this->actions;
    }

    /**
     * Get submit actions by groups.
     */
    public function getSubmitActionGroups(): array
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
     *                      'template'         => (required) template to use for the field's HTML eg AcmeMyBundle:FormField:thefield.html.twig
     *                      'formTypeOptions'  => (optional) array of options to pass to formType
     *                      'formTheme'        => (optional) theme for custom form view
     *                      'valueFilter'      => (optional) the filter to use to clean the input as supported by InputHelper or a callback;
     *                      should accept arguments FormField $field and $filteredValue
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
     * @throws BadConfigurationException
     */
    public function addFormField($key, array $field): void
    {
        if (array_key_exists($key, $this->fields)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another field. Please use a different key.");
        }

        $callbacks = [];

        // Only validate valueFilter if it's not a InputHelper method
        if (isset($field['valueFilter'])
            && (!is_string($field['valueFilter'])
                || !is_callable(
                    [\Mautic\CoreBundle\Helper\InputHelper::class, $field['valueFilter']]
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
     *                         $validator = [
     *                         'eventName' => (required) Event name to dispatch to validate the form; it will recieve a ValidationEvent object
     *                         'fieldType' => (optional) Optional filter to validate only a specific type of field; otherwise every field
     *                         will be sent through the validation event
     *                         ]
     */
    public function addValidator($key, array $validator): void
    {
        if (array_key_exists($key, $this->fields)) {
            throw new \InvalidArgumentException("The key, '$key' is already used by another validator. Please use a different key.");
        }

        // check for required keys and that given functions are callable
        $this->verifyComponent(['eventName'], $validator);

        $this->validators[$key] = $validator;
    }

    /**
     * @param FormInterface<object> $form
     */
    public function addValidatorsToBuilder(FormInterface $form): void
    {
        if (!empty($this->validators)) {
            $validationData = $form->getData()['validation'] ?? [];
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
     */
    public function getValidators(): array
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
