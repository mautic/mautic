<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadFieldValue;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class LeadModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class LeadModel extends FormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository = 'MauticLeadBundle:Lead';
    }


    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null)
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(array('Lead'), 'Entity must be of class Lead()');
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('lead', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Lead();
        }

        $entity = parent::getEntity($id);


        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(array('Lead'), 'Entity must be of class Lead()');
        }

        if (empty($event)) {
            $event = new LeadEvent($entity, $isNew);
            $event->setEntityManager($this->em);
        }

        switch ($action) {
            case "pre_save":
                $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_SAVE, $event);
                break;
            case "post_save":
                $this->dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
                break;
            case "pre_delete":
                $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_DELETE, $event);
                break;
            case "post_delete":
                $this->dispatcher->dispatch(LeadEvents::LEAD_POST_DELETE, $event);
                break;
        }

        return $event;
    }

    /**
     * Parses the custom field values for a lead into an array of LeadFieldValue entities
     *
     * @param Lead  $lead
     * @param array $data
     * @return array
     */
    public function setFieldValues(Lead &$lead, array $data)
    {
        //save the field values
        $fieldValues   = $lead->getFields();
        $fieldModel    = $this->factory->getModel('leadfield');
        $fields        = $fieldModel->getEntities();
        $updatedFields = array();
        //update existing values
        foreach ($fieldValues as $v) {
            $field   = $v->getField();
            $alias   = $field->getAlias();
            $value   = (isset($data["field_{$alias}"])) ?
                $data["field_{$alias}"] : "";
            if ($v->getValue() !== $value) {
                $v->setValue($value);

                //take note of updated field
                $lead->addFieldValue($v->getField()->getLabel(), $value, $v, true);
            }
            $updatedFields[$field->getId()] = 1;
        }

        foreach ($fields as $field) {
            if (isset($updatedFields[$field->getId()]) || !$field->getIsVisible())
                continue;
            $value = $data["field_{$field->getAlias()}"];
            $fieldValue = new LeadFieldValue();
            $fieldValue->setLead($lead);
            $fieldValue->setField($field);
            $fieldValue->setValue($value);
            $lead->addField($fieldValue);
        }
    }

    /**
     * Disassociates a user from leads
     *
     * @param $userId
     */
    public function disassociateOwner($userId)
    {
        $leads = $this->em->getRepository($this->repository)->findByOwner($userId);
        foreach ($leads as $lead) {
            $lead->setOwner(null);
            $this->saveEntity($lead);
        }
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'user':
                $results = $this->em->getRepository('MauticUserBundle:User')->getUserList($filter, $limit, 0, array('lead' => 'leads'));
                break;
        }

        return $results;
    }

    /**
     * Obtain an array of users for api lead edits
     *
     * @return mixed
     */
    public function getOwnerList()
    {
        $results = $this->em->getRepository('MauticUserBundle:User')->getUserList('', 0);
        return $results;
    }
}