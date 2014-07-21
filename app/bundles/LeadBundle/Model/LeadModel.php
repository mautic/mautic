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
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;
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
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Lead');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:leads';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getNameGetter()
    {
        return "getPrimaryIdentifier";
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
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

        switch ($action) {
            case "pre_save":
                $name = LeadEvents::LEAD_PRE_SAVE;
                break;
            case "post_save":
                $name = LeadEvents::LEAD_POST_SAVE;
                break;
            case "pre_delete":
                $name = LeadEvents::LEAD_PRE_DELETE;
                break;
            case "post_delete":
                $name = LeadEvents::LEAD_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * Parses the custom field values for a lead into an array of LeadFieldValue entities
     *
     * @param Lead  $lead
     * @param array $data
     * @param $overwriteWithBlank
     * @return array
     */
    public function setFieldValues(Lead &$lead, array $data, $overwriteWithBlank = true)
    {
        //gleam social networks if there is an email and applicable
        if (!empty($data["field_email"])) {
            $serviceObjects  = NetworkIntegrationHelper::getNetworkObjects($this->factory);
            $socialMediaData = array();
            foreach ($serviceObjects as $sm) {
                $service  = $sm->getName();
                $settings = $sm->getSettings();
                $fields   = $settings->getLeadFields();
                $features = $settings->getSupportedFeatures();
                if (in_array('lead_fields', $features) && $settings->isPublished() && !empty($fields)) {
                    if (method_exists($sm, 'getUserData')) {
                        //make the call and retrieve data
                        $socialMediaData[$service]['fields'] = $fields;
                        $socialMediaData[$service]['data']   = $sm->getUserData($data);
                    }
                }
            }
        }

        //save the field values
        $fieldValues   = $lead->getFields();
        $fieldModel    = $this->factory->getModel('lead.field');
        $fields        = $fieldModel->getEntities();
        $updatedFields = array();

        //update existing values
        foreach ($fieldValues as $v) {
            $field   = $v->getField();
            $alias   = $field->getAlias();
            $value   = (isset($data["field_{$alias}"])) ?
                $data["field_{$alias}"] : "";
            if ($v->getValue() !== $value && (!empty($value) || (empty($value) && $overwriteWithBlank))) {
                $v->setValue($value);
            }

            //if empty, check for social media data to plug the hole
            if (empty($value) && !empty($socialMediaData)) {
                foreach ($socialMediaData as $service => $details) {
                    //check to see if a field has been assigned
                    if (in_array($field->getId(), $details['fields'])) {

                        //check to see if the data is available
                        $key = array_search($field->getId(), $details['fields']);
                        if (isset($details['data'][$key])) {
                            //Found!!
                            $v->setValue($details['data'][$key]);
                            break;
                        }
                    }
                }
            }
            $updatedFields[$field->getId()] = 1;
        }

        //find and write new ones
        foreach ($fields as $field) {
            if (isset($updatedFields[$field->getId()]) || !$field->getIsVisible())
                continue;
            $value = $data["field_{$field->getAlias()}"];

            //if empty, check for social media data to plug the hole
            if (empty($value) && !empty($socialMediaData)) {
                foreach ($socialMediaData as $service => $details) {
                    //check to see if a field has been assigned
                    if (in_array($field->getId(), $details['fields'])) {

                        //check to see if the data is available
                        $key = array_search($field->getId(), $details['fields']);
                        if (isset($details['data'][$key])) {
                            //Found!!
                            $value = $details['data'][$key];
                            break;
                        }
                    }
                }
            }
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
        $leads = $this->getRepository()->findByOwner($userId);
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

    /**
     * Reorganizes a field value persistent collection to be keyed by field alias
     *
     * @param $fieldValues
     * @return array
     */
    public function organizeFieldsByAlias($fieldValues)
    {
        $array = array();
        foreach ($fieldValues as $v) {
            $field = $v->getField();
            $array[$field->getAlias()]['id']    = $field->getId();
            $array[$field->getAlias()]['label'] = $field->getLabel();
            $array[$field->getAlias()]['alias'] = $field->getLabel();
            $array[$field->getAlias()]['value'] = $v->getValue();
            $array[$field->getAlias()]['type']  = $field->getType();
        }
        return $array;
    }
}