<?php

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\FormFieldEvent;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class FieldModel extends CommonFormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var LeadFieldModel
     */
    protected $leadFieldModel;

    public function __construct(LeadFieldModel $leadFieldModel)
    {
        $this->leadFieldModel = $leadFieldModel;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        list($fields, $choices)               = $this->getObjectFields('lead');
        list($companyFields, $companyChoices) = $this->getObjectFields('company');

        // Only show the lead fields not already used
        $usedLeadFields   = $this->session->get('mautic.form.'.$entity['formId'].'.fields.leadfields', []);
        $testLeadFields   = array_flip($usedLeadFields);
        $currentLeadField = (isset($entity['leadField'])) ? $entity['leadField'] : null;
        if (!empty($currentLeadField) && isset($testLeadFields[$currentLeadField])) {
            unset($testLeadFields[$currentLeadField]);
        }

        foreach ($choices as &$group) {
            $group = array_diff_key($group, $testLeadFields);
        }

        $options['leadFields']['lead']          = $choices;
        $options['leadFieldProperties']['lead'] = $fields;

        $options['leadFields']['company']          = $companyChoices;
        $options['leadFieldProperties']['company'] = $companyFields;

        if ($action) {
            $options['action'] = $action;
        }

        return $formFactory->create(FieldType::class, $entity, $options);
    }

    public function getObjectFields($object = 'lead')
    {
        $fields  = $this->leadFieldModel->getFieldListWithProperties($object);
        $choices = [];

        foreach ($fields as $alias => $field) {
            if (empty($field['isPublished'])) {
                continue;
            }
            if (!isset($choices[$field['group_label']])) {
                $choices[$field['group_label']] = [];
            }
            $choices[$field['group_label']][$field['label']] = $alias;
        }

        return [$fields, $choices];
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\FormBundle\Entity\FieldRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Field');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'form:forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Field();
        }

        return parent::getEntity($id);
    }

    /**
     * Get the fields saved in session.
     *
     * @param $formId
     *
     * @return array
     */
    public function getSessionFields($formId)
    {
        $fields = $this->session->get('mautic.form.'.$formId.'.fields.modified', []);
        $remove = $this->session->get('mautic.form.'.$formId.'.fields.deleted', []);

        return array_diff_key($fields, array_flip($remove));
    }

    /**
     * @param $label
     * @param $aliases
     *
     * @return string
     */
    public function generateAlias($label, &$aliases)
    {
        $alias = $this->cleanAlias($label, 'f_', 25);

        //make sure alias is not already taken
        $testAlias = $alias;

        $count    = (int) in_array($alias, $aliases);
        $aliasTag = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $count     = (int) in_array($testAlias, $aliases);
            ++$aliasTag;
        }

        // Prevent internally used identifiers in the form HTML from colliding with the generated field's ID
        $internalUse = ['message', 'error', 'id', 'return', 'name', 'messenger'];
        if (in_array($testAlias, $internalUse)) {
            $testAlias = 'f_'.$testAlias;
        }

        $aliases[] = $testAlias;

        return $testAlias;
    }

    /**
     * @return FormFieldEvent|Event|void|null
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Field) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        switch ($action) {
            case 'pre_save':
                $name = FormEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $name = FormEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = FormEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = FormEvents::FIELD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FormFieldEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }
}
