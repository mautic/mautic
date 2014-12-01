<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
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
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     * @return mixed
     */
    public function getEntities(array $args = array())
    {
        //set the point trigger model in order to get the color code for the lead
        $repo = $this->getRepository();
        $repo->setTriggerModel($this->factory->getModel('point.trigger'));

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead                                $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(array('Lead'), 'Entity must be of class Lead()');
        }
        if (!empty($action))  {
            $options['action'] = $action;
        }
        return $formFactory->create('lead', $entity, $options);
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

        //set the point trigger model in order to get the color code for the lead
        $repo = $this->getRepository();
        $repo->setTriggerModel($this->factory->getModel('point.trigger'));

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
     * Populates custom field values for updating the lead. Also retrieves social media data
     *
     * @param Lead  $lead
     * @param array $data
     * @param $overwriteWithBlank
     * @return array
     */
    public function setFieldValues(Lead &$lead, array $data, $overwriteWithBlank = true)
    {
        //@todo - add a catch to NOT do social gleaning if a lead is created via a form, etc as we do not want the user to experience the wait
        //generate the social cache
        list($socialCache, $socialFeatureSettings) = $this->factory->getHelper('integration')->getUserProfiles($lead, $data, true, null, false, true);

        $isNew = ($lead->getId()) ? false : true;

        //set the social cache while we have it
        $lead->setSocialCache($socialCache);

        //save the field values
        if (!$isNew && !$lead->isNewlyCreated()) {
            $fieldValues = $lead->getFields();
        } else {
            static $fields;
            if (empty($fields)) {
                $fields = $this->factory->getModel('lead.field')->getEntities(array(
                    'filter'         => array('isPublished' => true),
                    'hydration_mode' => 'HYDRATE_ARRAY'
                ));
                $fields = $this->organizeFieldsByGroup($fields);
            }
            $fieldValues = $fields;
        }

        //update existing values
        foreach ($fieldValues as $group => &$groupFields) {
            foreach ($groupFields as $alias => &$field) {
                if (!isset($field['value'])) {
                    $field['value'] = null;
                }

                $curValue = $field['value'];
                $newValue = (isset($data[$alias])) ? $data[$alias] : "";
                if ($curValue !== $newValue && (!empty($newValue) || (empty($newValue) && $overwriteWithBlank))) {
                    $field['value'] = $newValue;
                    $lead->addUpdatedField($alias, $newValue, $curValue);
                }

                //if empty, check for social media data to plug the hole
                if (empty($newValue) && !empty($socialCache)) {
                    foreach ($socialCache as $service => $details) {
                        //check to see if a field has been assigned

                        if (!empty($socialFeatureSettings[$service]['leadFields']) &&
                            in_array($field['id'], $socialFeatureSettings[$service]['leadFields'])
                        ) {

                            //check to see if the data is available
                            $key = array_search($field['id'], $socialFeatureSettings[$service]['leadFields']);
                            if (isset($details['profile'][$key])) {
                                //Found!!
                                $field['value'] = $details['profile'][$key];
                                $lead->addUpdatedField($alias, $details['profile'][$key]);
                                break;
                            }
                        }
                    }
                }
            }
        }

        $lead->setFields($fieldValues);
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
     * @param $start
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0)
    {
        $results = array();
        switch ($type) {
            case 'user':
                $results = $this->em->getRepository('MauticUserBundle:User')->getUserList($filter, $limit, $start, array('lead' => 'leads'));
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
     * Obtains a list of leads based off IP
     *
     * @param $ip
     *
     * @return mixed
     */
    public function getLeadsByIp($ip)
    {
        return $this->getRepository()->getLeadsByIp($ip);
    }

    /**
     * Gets the details of a lead
     *
     * @param $lead
     *
     * @return mixed
     */
    public function getLeadDetails($lead)
    {
        static $details = array();

        $leadId = ($lead instanceof Lead) ? $lead->getId() : (int) $lead;

        if (!isset($details[$leadId])) {
            $details[$leadId] = $this->getRepository()->getFieldValues($leadId);
        }

        return $details[$leadId];
    }

    /**
     * Reorganizes a field list to be keyed by field's group then alias
     *
     * @param $fields
     * @return array
     */
    public function organizeFieldsByGroup($fields)
    {
        $array = array();

        foreach ($fields as $field) {
            if ($field instanceof LeadField) {
                $alias = $field->getAlias();
                if ($field->isPublished()) {
                    $group                          = $field->getGroup();
                    $array[$group][$alias]['id']    = $field->getId();
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field->getLabel();
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field->getType();
                }
            } else {
                $alias = $field['alias'];
                if ($field['isPublished']) {
                    $group = $field['group'];
                    $array[$group][$alias]['id']    = $field['id'];
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field['label'];
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field['type'];
                }
            }
        }

        //make sure each group key is present
        $groups = array('core', 'social', 'personal', 'professional');
        foreach ($groups as $g) {
            if (!isset($array[$g])) {
                $array[$g] = array();
            }
        }

        return $array;
    }

    /**
     * Returns flat array for single lead
     *
     * @param $leadId
     */
    public function getLead($leadId)
    {
        return $this->getRepository()->getLead($leadId);
    }

    /**
     * Get the current lead; if $returnTracking = true then array with lead, trackingId, and boolean of if trackingId
     * was just generated or not
     *
     * @return Lead|array
     */
    public function getCurrentLead($returnTracking = false)
    {
        static $lead;

        $request = $this->factory->getRequest();
        $cookies = $request->cookies;

        list($trackingId, $generated) = $this->getTrackingCookie();

        if (empty($lead)) {
            $leadId = $cookies->get($trackingId);
            $ip     = $this->factory->getIpAddress();
            if (empty($leadId)) {
                //this lead is not tracked yet so get leads by IP and track that lead or create a new one
                $leads = $this->getLeadsByIp($ip->getIpAddress());

                if (count($leads)) {
                    //just create a tracking cookie for the newest lead
                    $lead   = $leads[0];
                    $leadId = $lead->getId();
                } else {
                    //let's create a lead
                    $lead = new Lead();
                    $lead->addIpAddress($ip);
                    $lead->setNewlyCreated(true);
                    $this->saveEntity($lead);
                    $leadId = $lead->getId();
                }
            } else {
                $lead = $this->getEntity($leadId);
                if ($lead === null) {
                    //let's create a lead
                    $lead = new Lead();
                    $lead->addIpAddress($ip);
                    $lead->setNewlyCreated(true);
                    $this->saveEntity($lead);
                    $leadId = $lead->getId();
                }
            }
            $this->setLeadCookie($leadId);
        }
        return ($returnTracking) ? array($lead, $trackingId, $generated) : $lead;
    }

    /**
     * Regenerate the lists this lead currently belongs to
     *
     * @param Lead $lead
     */
    public function regenerateLeadLists(Lead $lead)
    {
        $lists = $this->getLists($lead);
        $model = $this->factory->getModel('lead.list');
        foreach ($lists as $lid => $list) {
            $model->regenerateListLeads($list);
        }
    }

    /**
     * Get a list of lists this lead belongs to
     *
     * @param $lead
     * @param bool $forLists
     *
     * @return mixed
     */
    public function getLists(Lead $lead, $forLists = false)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');
        return $repo->getLeadLists($lead->getId(), $forLists);
    }

    /**
     * Get or generate the tracking ID for the current session
     *
     * @return array
     */
    public function getTrackingCookie()
    {
        static $trackingId = false, $generated = false;

        if (empty($trackingId)) {
            $request = $this->factory->getRequest();
            $cookies = $request->cookies;

            //check for the tracking cookie
            $trackingId = $cookies->get('mautic_session_id');
            $generated  = false;
            if (empty($trackingId)) {
                $trackingId = hash('sha1', uniqid(mt_rand()));
                $generated  = true;
            }

            //create a tracking cookie
            $this->factory->getHelper('cookie')->setCookie('mautic_session_id', $trackingId);
        }

        return array($trackingId, $generated);
    }

    /**
     * Sets the leadId for the current session
     *
     * @param $leadId
     */
    public function setLeadCookie($leadId)
    {
        list($trackingId, $generated) = $this->getTrackingCookie();
        $this->factory->getHelper('cookie')->setCookie($trackingId, $leadId);
    }

    /**
     * @param $lead
     * @param $lists
     */
    public function addToLists($lead, $lists)
    {
        static $foundLists = array();

        $leadListModel = $this->factory->getModel('lead.list');
        $leadListRepo  = $leadListModel->getRepository();

        if (!$lists instanceof LeadList) {
            if (!is_array($lists)) {
                $lists = array($lists);
            }

            //make sure they are ints
            $searchForLists = array();
            foreach ($lists as $k => &$l) {
                $l = (int) $l;
                if (!isset($foundLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $leadListModel->getEntities(array(
                    'filter' => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists
                            )
                        )
                    )
                ));

                foreach ($listEntities as $list) {
                    $foundLists[$list->getId()] = $list;
                }
            }

            $persist = array();
            foreach ($lists as $l) {
                $foundLists[$l]->addLead($lead);
                $persist[] = $foundLists[$l];

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                    $event = new ListChangeEvent($lead, $foundLists[$l], true);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
                }
            }

            $leadListRepo->saveEntities($persist);
        } else {
            $lists->addLead($lead);
            $leadListRepo->saveEntity($lists);

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                $event = new ListChangeEvent($lead, $lists, true);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
            }
        }
    }

    /**
     * @param $lead
     * @param $lists
     */
    public function removeFromLists($lead, $lists)
    {
        static $foundLists = array();

        $leadListModel = $this->factory->getModel('lead.list');
        $leadListRepo  = $leadListModel->getRepository();

        if (!$lists instanceof LeadList) {
            if (!is_array($lists)) {
                $lists = array($lists);
            }

            //make sure they are ints
            $searchForLists = array();
            foreach ($lists as $k => &$l) {
                $l = (int) $l;
                if (!isset($foundLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $leadListModel->getEntities(array(
                    'filter' => array(
                        'force' => array(
                            array(
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists
                            )
                        )
                    )
                ));

                foreach ($listEntities as $list) {
                    $foundLists[$list->getId()] = $list;
                }
            }

            $persist = array();
            foreach ($lists as $l) {
                $foundLists[$l]->removeLead($lead);
                $persist[] = $foundLists[$l];

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                    $event = new ListChangeEvent($lead, $foundLists[$l], false);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
                }
            }

            $leadListRepo->saveEntities($persist);
        } else {
            $lists->removeLead($lead);
            $leadListRepo->saveEntity($lists);

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                $event = new ListChangeEvent($lead, $lists, false);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
            }
        }
    }

    /**
     * Merge two leads; if a conflict of data occurs, the newest lead will get precedence
     *
     * @param Lead $lead
     * @param Lead $lead2
     */
    public function mergeLeads(Lead $lead, Lead $lead2)
    {
        $leadId  = $lead->getId();
        $lead2Id = $lead2->getId();

        //if they are the same lead, then just return one
        if ($leadId === $lead2Id) {
            return $lead;
        }

        //which lead is the oldest?
        $oldLead  = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
        $newLead  = ($oldLead->getId() === $leadId) ? $lead2 : $lead;

        //merge IP addresses
        $ipAddresses = $newLead->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $oldLead->addIpAddress($ip);
        }

        //merge fields
        $newLeadFields = $newLead->getFields();
        foreach ($newLeadFields as $group => $groupFields) {
            foreach ($groupFields as $alias => $value) {
                //overwrite old lead's data with new lead's if new lead's is not empty
                if (!empty($value)) {
                    $oldLead->addUpdatedField($alias, $value);
                }
            }
        }

        //merge owner
        $oldOwner = $oldLead->getOwner();
        $newOwner = $newLead->getOwner();

        if ($oldOwner === null) {
            $oldLead->setOwner($newOwner);
        }

        //save the updated lead
        $this->saveEntity($oldLead, false);

        //delete the old
        $this->deleteEntity($newLead);

        //return the merged lead
        return $oldLead;
    }
}
