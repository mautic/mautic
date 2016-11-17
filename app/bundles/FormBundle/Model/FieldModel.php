<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class FieldModel.
 */
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

    /**
     * FieldModel constructor.
     *
     * @param LeadFieldModel $leadFieldModel
     */
    public function __construct(LeadFieldModel $leadFieldModel)
    {
        $this->leadFieldModel = $leadFieldModel;
    }

    /**
     * @param Session $session
     */
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
        $fields  = $this->leadFieldModel->getFieldListWithProperties();
        $choices = [];

        foreach ($fields as $alias => $field) {
            if (!isset($choices[$field['group_label']])) {
                $choices[$field['group_label']] = [];
            }

            $choices[$field['group_label']][$alias] = $field['label'];
        }

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

        $options['leadFields']          = $choices;
        $options['leadFieldProperties'] = $fields;

        if ($action) {
            $options['action'] = $action;
        }

        return $formFactory->create('formfield', $entity, $options);
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
        if ($id === null) {
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
}
