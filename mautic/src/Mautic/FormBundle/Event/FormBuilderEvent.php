<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FormBuilderEvent
 *
 * @package Mautic\FormBundle\Event
 */
class FormBuilderEvent extends Event
{
    private $actions = array();
    private $fields  = array();
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds a submit action to the list of available actions.
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array $action - can contain the following keys:
     *  'group'    => (required) translation string to group actions by
     *  'label'    => (required) what to display in the list
     *  'descr'    => (optional) short description of event
     *  'template' => (optional) template to use for the action's HTML in the form builder
     *      i.e AcmeMyBundle:FormAction:theaction.html.php
     *  'formType' => (required) name of the form type SERVICE for the action
     *  'callback' => (required) callback function that will be passed the results upon a form submit.
     *      The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *          array $fields - form fields with keys id, type and alias
     *          array $properties - values saved from the formType as defined here
     *          array $post - values from submitted form
     *          array $server - values from Request $request->server->all()
     *          Mautic\CoreBundle\Factory\MauticFactory $factory
     *          array $feedback whatever is returned from other function subscribed to this event will be stored stored
     *                in this variable with the $key as its index; can be used to store new entities, etc that can
     *                be used by other subscribers
     *          Mautic\FormBundle\Entity\Action $action
     *          Mautic\FormBundle\Entity\Form $form
     */
    public function addSubmitAction($key, array $action)
    {
        if (array_key_exists($key, $this->actions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('group', 'label', 'formType', 'callback'),
            array('callback'),
            $action
        );

        //translate the group
        $action['group'] = $this->translator->trans($action['group']);
        $this->actions[$key] = $action;
    }

    /**
     * Get submit actions
     *
     * @return array
     */
    public function getSubmitActions()
    {
        uasort($this->actions, function ($a, $b) {
            return strnatcmp(
                $a['group'], $b['group']);
        });
        return $this->actions;
    }

    /**
     * Adds a form field to the list of available fields in the form builder.
     *
*@param string $key - unique identifier; it is recommended that it be namespaced i.e. leadbundle.myfield
     * @param array $action - must contain the following keys
     *  'label'    => (required) what to display in the list
     *  'formType' => (required) name of the form type SERVICE for the field's property column
     *  'template' => (required) template to use for the field's HTML i.e AcmeMyBundle:FormField:thefield.html.php
     *  'valueFilter' = (optional)the filter to use to clean the input as supported by InputHelper or a callback function that accepts
     *      the variables FormField $field and $value
     *  'valueConstraints' = (optional) callback function to use to validate the value; FormField $field and $filteredValue are passed in
     *  'builderOptions'  => (optional) array of options:
     *      addHelpMessage = true|false
     *      addShowLabel = true|false
     *      addDefaultValue = true|false
     *      addLabelAttributes = true|false
     *      addInputAttributes = true|false
     *      addIsRequired = true|false
     */

    public function addFormField($key, array $field)
    {
        if (array_key_exists($key, $this->fields)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another field. Please use a different key.");
        }
        $this->verifyComponent(array('label', 'formType', 'template'), array(), $field);

        $this->fields[$key] = $field;
    }

    /**
     * Get form fields
     *
     * @return mixed
     */
    public function getFormFields()
    {
        return $this->fields;
    }

    /**
     * @param array $component
     */
    private function verifyComponent(array $keys, array $methods, array $component)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $component)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($component[$m]) && !is_callable($component[$m], true)) {
                throw new InvalidArgumentException($component[$m] . ' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}