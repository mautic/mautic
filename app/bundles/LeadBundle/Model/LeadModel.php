<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\EmailBundle\Entity\DoNotEmail;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class LeadModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class LeadModel extends FormModel
{
    private $currentLead       = null;
    private $systemCurrentLead = null;

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\LeadRepository
     */
    public function getRepository()
    {
        static $repoSetup;

        $repo = $this->em->getRepository('MauticLeadBundle:Lead');

        if (!$repoSetup) {
            $repoSetup = true;

            //set the point trigger model in order to get the color code for the lead
            $repo->setTriggerModel($this->factory->getModel('point.trigger'));

            /** @var FieldModel $fieldModel */
            $fieldModel = $this->factory->getModel('lead.field');
            $fields     = $fieldModel->getFieldList(true, false);

            $socialFields = (!empty($fields['social'])) ? array_keys($fields['social']) : array();
            $repo->setAvailableSocialFields($socialFields);

            $searchFields = array();
            foreach ($fields as $group => $groupFields) {
                $searchFields = array_merge($searchFields, array_keys($groupFields));
            }
            $repo->setAvailableSearchFields($searchFields);
        }

        return $repo;
    }

    /**
     * Get the tags repository
     *
     * @return \Mautic\LeadBundle\Entity\TagRepository
     */
    public function getTagRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Tag');
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
     * @return null|Lead
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
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead $entity
     * @param bool   $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        //check to see if we can glean information from ip address
        if (!$entity->imported && count($ips = $entity->getIpAddresses())) {
            $fields = $entity->getFields();

            $details = $ips->first()->getIpDetails();
            if (!empty($details['city']) && empty($fields['core']['city']['value'])) {
                $entity->addUpdatedField('city', $details['city']);
            }

            if (!empty($details['region']) && empty($fields['core']['state']['value'])) {
                $entity->addUpdatedField('state', $details['region']);
            }

            if (!empty($details['country']) && empty($fields['core']['country']['value'])) {
                $entity->addUpdatedField('country', $details['country']);
            }
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        // Delete custom avatar if one exists
        $imageDir = $this->factory->getSystemPath('images', true);
        $avatar   = $imageDir . '/lead_avatars/avatar' . $entity->getId();

        if (file_exists($avatar)) {
            unlink($avatar);
        }

        parent::deleteEntity($entity);
    }

    /**
     * Populates custom field values for updating the lead. Also retrieves social media data
     *
     * @param Lead  $lead
     * @param array $data
     * @param $overwriteWithBlank
     * @return array
     */
    public function setFieldValues(Lead &$lead, array $data, $overwriteWithBlank = false)
    {
        //@todo - add a catch to NOT do social gleaning if a lead is created via a form, etc as we do not want the user to experience the wait
        //generate the social cache
        list($socialCache, $socialFeatureSettings) = $this->factory->getHelper('integration')->getUserProfiles($lead, $data, true, null, false, true);

        //set the social cache while we have it
        if (!empty($socialCache)) {
            $lead->setSocialCache($socialCache);
        }

        //save the field values
        $fieldValues = $lead->getFields();

        if (empty($fieldValues)) {
            // Lead is new or they haven't been populated so let's build the fields now
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

                // Only update fields that are part of the passed $data array
                if (array_key_exists($alias, $data)) {
                    $curValue = $field['value'];
                    $newValue = $data[$alias];

                    if ($curValue !== $newValue && (!empty($newValue) || (empty($newValue) && $overwriteWithBlank))) {
                        $field['value'] = $newValue;
                        $lead->addUpdatedField($alias, $newValue, $curValue);
                    }

                    //if empty, check for social media data to plug the hole
                    if (empty($newValue) && !empty($socialCache)) {
                        foreach ($socialCache as $service => $details) {
                            //check to see if a field has been assigned

                            if (!empty($socialFeatureSettings[$service]['leadFields'])
                                && in_array($field['alias'], $socialFeatureSettings[$service]['leadFields'])
                            ) {

                                //check to see if the data is available
                                $key = array_search($field['alias'], $socialFeatureSettings[$service]['leadFields']);
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
     * Gets the details of a lead if not already set
     *
     * @param $lead
     *
     * @return mixed
     */
    public function getLeadDetails($lead)
    {
        static $details = array();

        if ($lead instanceof Lead) {
            $fields = $lead->getFields();
            if (!empty($fields)) {

                return $fields;
            }
        }

        $leadId = ($lead instanceof Lead) ? $lead->getId() : (int) $lead;

        return $this->getRepository()->getFieldValues($leadId);
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
     * Takes leads organized by group and flattens them into just alias => value
     *
     * @param $fields
     *
     * @return array
     */
    public function flattenFields($fields)
    {
        $flat = array();
        foreach ($fields as $group => $fields) {
            foreach ($fields as $field) {
                $flat[$field['alias']] = $field['value'];
            }
        }

        return $flat;
    }

    /**
     * Returns flat array for single lead
     *
     * @param $leadId
     *
     * @return array
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
        if (!$returnTracking && $this->systemCurrentLead) {
            // Just return the system set lead
            return $this->systemCurrentLead;
        }

        $request = $this->factory->getRequest();
        $cookies = $request->cookies;

        list($trackingId, $generated) = $this->getTrackingCookie();

        if (empty($this->currentLead)) {
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

                    // Set to prevent loops
                    $this->currentLead = $lead;

                    $this->saveEntity($lead, false);
                    $leadId = $lead->getId();
                }

                $fields = $this->getLeadDetails($lead);
                $lead->setFields($fields);
            } else {
                $lead = $this->getEntity($leadId);

                if ($lead === null) {
                    //let's create a lead
                    $lead = new Lead();
                    $lead->addIpAddress($ip);
                    $lead->setNewlyCreated(true);

                    // Set to prevent loops
                    $this->currentLead = $lead;

                    $this->saveEntity($lead, false);
                    $leadId = $lead->getId();

                    $fields = $this->getLeadDetails($lead);
                    $lead->setFields($fields);
                }
            }

            $this->currentLead = $lead;
            $this->setLeadCookie($leadId);
        }

        // Log last active
        if (!defined('MAUTIC_LEAD_LASTACTIVE_LOGGED')) {
            $this->getRepository()->updateLastActive($this->currentLead->getId());
            define('MAUTIC_LEAD_LASTACTIVE_LOGGED', 1);
        }

        return ($returnTracking) ? array($this->currentLead, $trackingId, $generated) : $this->currentLead;
    }

    /**
     * Sets current lead
     *
     * @param Lead $lead
     */
    public function setCurrentLead(Lead $lead)
    {
        if ($this->systemCurrentLead) {
            // Overwrite system current lead
            $this->systemCurrentLead = $lead;

            return;
        }

        $oldLead = (is_null($this->currentLead)) ? $this->getCurrentLead() : $this->currentLead;

        $fields = $lead->getFields();
        if (empty($fields)) {
            $lead->setFields($this->getLeadDetails($lead));
        }

        $this->currentLead = $lead;

        if ($oldLead->getId() != $lead->getId()) {

            list($newTrackingId, $oldTrackingId) = $this->getTrackingCookie(true);

            //set the tracking cookies
            $this->setLeadCookie($lead->getId());

            if ($this->dispatcher->hasListeners(LeadEvents::CURRENT_LEAD_CHANGED)) {
                $event = new LeadChangeEvent($oldLead, $oldTrackingId, $lead, $newTrackingId);
                $this->dispatcher->dispatch(LeadEvents::CURRENT_LEAD_CHANGED, $event);
            }
        }
    }

    /**
     * Used by system processes that hook into events that use getCurrentLead()
     *
     * @param Lead $lead
     */
    function setSystemCurrentLead(Lead $lead = null)
    {
        $fields = $lead->getFields();
        if (empty($fields)) {
            $lead->setFields($this->getLeadDetails($lead));
        }

        $this->systemCurrentLead = $lead;
    }

    /**
     * Get a list of lists this lead belongs to
     *
     * @param Lead       $lead
     * @param bool|false $forLists
     * @param bool|false $arrayHydration
     *
     * @return mixed
     */
    public function getLists(Lead $lead, $forLists = false, $arrayHydration = false)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');
        return $repo->getLeadLists($lead->getId(), $forLists, $arrayHydration);
    }

    /**
     * Get or generate the tracking ID for the current session
     *
     * @return array
     */
    public function getTrackingCookie($forceRegeneration = false)
    {
        static $trackingId = false, $generated = false;

        $request = $this->factory->getRequest();
        $cookies = $request->cookies;

        if ($forceRegeneration) {
            $generated = true;

            $oldTrackingId = $cookies->get('mautic_session_id');
            $trackingId    = hash('sha1', uniqid(mt_rand()));

            //create a tracking cookie
            $this->factory->getHelper('cookie')->setCookie('mautic_session_id', $trackingId);

            return array($trackingId, $oldTrackingId);
        }

        if (empty($trackingId)) {
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
        // Remove the old if set
        $request       = $this->factory->getRequest();
        $cookies       = $request->cookies;
        $oldTrackingId = $cookies->get('mautic_session_id');
        if (!empty($oldTrackingId)) {
            $this->factory->getHelper('cookie')->setCookie($oldTrackingId, null, -3600);
        }

        list($trackingId, $generated) = $this->getTrackingCookie();
        $this->factory->getHelper('cookie')->setCookie($trackingId, $leadId);
    }

    /**
     * Add lead to lists
     *
     * @param array|Lead        $lead
     * @param array|LeadList    $lists
     * @param bool              $manuallyAdded
     */
    public function addToLists($lead, $lists, $manuallyAdded = true)
    {
        $this->factory->getModel('lead.list')->addLead($lead, $lists, $manuallyAdded);
    }

    /**
     * Remove lead from lists
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyRemoved
     */
    public function removeFromLists($lead, $lists, $manuallyRemoved = true)
    {
        $this->factory->getModel('lead.list')->removeLead($lead, $lists, $manuallyRemoved);
    }

    /**
     * Merge two leads; if a conflict of data occurs, the newest lead will get precedence
     *
     * @param Lead $lead
     * @param Lead $lead2
     * @param bool $autoMode If true, the newest lead will be merged into the oldes then deleted; otherwise, $lead will be merged into $lead2 then deleted
     *
     * @return Lead
     */
    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true)
    {
        $leadId  = $lead->getId();
        $lead2Id = $lead2->getId();

        //if they are the same lead, then just return one
        if ($leadId === $lead2Id) {
            return $lead;
        }

        if ($autoMode) {
            //which lead is the oldest?
            $mergeWith = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
            $mergeFrom = ($mergeWith->getId() === $leadId) ? $lead2 : $lead;
        } else {
            $mergeWith = $lead2;
            $mergeFrom = $lead;
        }

        //dispatch pre merge event
        $event = new LeadMergeEvent($mergeWith, $mergeFrom);
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_PRE_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);
        }

        //merge IP addresses
        $ipAddresses = $mergeFrom->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $mergeWith->addIpAddress($ip);
        }

        //merge fields
        $mergeFromFields = $mergeFrom->getFields();
        foreach ($mergeFromFields as $group => $groupFields) {
            foreach ($groupFields as $alias => $details) {
                //overwrite old lead's data with new lead's if new lead's is not empty
                if (!empty($details['value'])) {
                    $mergeWith->addUpdatedField($alias, $details['value']);
                }
            }
        }

        //merge owner
        $oldOwner = $mergeWith->getOwner();
        $newOwner = $mergeFrom->getOwner();

        if ($oldOwner === null) {
            $mergeWith->setOwner($newOwner);
        }

        //sum points
        $mergeWithPoints = $mergeWith->getPoints();
        $mergeFromPoints = $mergeFrom->getPoints();
        $mergeWith->setPoints($mergeWithPoints + $mergeFromPoints);

        //merge tags
        $mergeFromTags = $mergeFrom->getTags();
        $addTags       = $mergeFromTags->getKeys();
        $this->modifyTags($mergeWith, $addTags, null, false);

        //save the updated lead
        $this->saveEntity($mergeWith, false);

        //post merge events
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_POST_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_POST_MERGE, $event);
        }

        //delete the old
        $this->deleteEntity($mergeFrom);

        //return the merged lead
        return $mergeWith;
    }

    /**
     * Add a do not contact entry for the lead
     *
     * @param Lead       $lead
     * @param string     $emailAddress
     * @param string     $reason
     * @param bool|true  $persist
     * @param bool|false $manual
     *
     * @return DoNotEmail|bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setDoNotContact(Lead $lead, $emailAddress = '', $reason = '', $persist = true, $manual = false)
    {
        if (empty($emailAddress)) {
            $emailAddress = $lead->getEmail();

            if (empty($emailAddress)) {
                return false;
            }
        }

        $em   = $this->factory->getEntityManager();
        $repo = $em->getRepository('MauticEmailBundle:Email');
        if (!$repo->checkDoNotEmail($emailAddress)) {
            $dnc = new DoNotEmail();
            $dnc->setLead($lead);
            $dnc->setEmailAddress($emailAddress);
            $dnc->setDateAdded(new \DateTime());
            $dnc->setUnsubscribed();
            $dnc->setManual($manual);
            $dnc->setComments($reason);

            if ($persist) {
                $repo->saveEntity($dnc);
            } else {
                $lead->addDoNotEmailEntry($dnc);

                return $dnc;
            }
        }

        return false;
    }

    /**
     * @param      $fields
     * @param      $data
     * @param null $owner
     * @param null $list
     * @param null $tags
     * @param bool $persist Persist to the database; otherwise return entity
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Swift_RfcComplianceException
     */
    public function importLead($fields, $data, $owner = null, $list = null, $tags = null, $persist = true)
    {
        // Let's check for an existing lead by email
        $hasEmail = (!empty($fields['email']) && !empty($data[$fields['email']]));
        if ($hasEmail) {
            // Validate the email
            MailHelper::validateEmail($data[$fields['email']]);

            $leadFound = $this->getRepository()->getLeadByEmail($data[$fields['email']]);
            $lead      = ($leadFound) ? $this->em->getReference('MauticLeadBundle:Lead', $leadFound['id']) : new Lead();
            $merged    = $leadFound;
        } else {
            $lead   = new Lead();
            $merged = false;
        }

        if (!empty($fields['dateAdded']) && !empty($data[$fields['dateAdded']])) {
            $dateAdded = new DateTimeHelper($data[$fields['dateAdded']]);
            $lead->setDateAdded($dateAdded->getUtcDateTime());
        }
        unset($fields['dateAdded']);

        if (!empty($fields['dateModified']) && !empty($data[$fields['dateModified']])) {
            $dateModified = new DateTimeHelper($data[$fields['dateModified']]);
            $lead->setDateModified($dateModified->getUtcDateTime());
        }
        unset($fields['dateModified']);

        if (!empty($fields['lastActive']) && !empty($data[$fields['lastActive']])) {
            $lastActive = new DateTimeHelper($data[$fields['lastActive']]);
            $lead->setLastActive($lastActive->getUtcDateTime());
        }
        unset($fields['lastActive']);

        if (!empty($fields['dateIdentified']) && !empty($data[$fields['dateIdentified']])) {
            $dateIdentified = new DateTimeHelper($data[$fields['dateIdentified']]);
            $lead->setDateIdentified($dateIdentified->getUtcDateTime());
        }
        unset($fields['dateIdentified']);

        if (!empty($fields['createdByUser']) && !empty($data[$fields['createdByUser']])) {
            $userRepo = $this->em->getRepository('MauticUserBundle:User');
            $createdByUser = $userRepo->findByIdentifier($data[$fields['createdByUser']]);
            if ($createdByUser !== null) {
                $lead->setCreatedBy($createdByUser);
            }
        }
        unset($fields['createdByUser']);

        if (!empty($fields['modifiedByUser']) && !empty($data[$fields['modifiedByUser']])) {
            $userRepo = $this->em->getRepository('MauticUserBundle:User');
            $modifiedByUser = $userRepo->findByIdentifier($data[$fields['modifiedByUser']]);
            if ($modifiedByUser !== null) {
                $lead->setModifiedBy($modifiedByUser);
            }
        }
        unset($fields['modifiedByUser']);

        if (!empty($fields['ip']) && !empty($data[$fields['ip']])) {
            $addresses = explode(',', $data[$fields['ip']]);
            foreach ($addresses as $address) {
                $ipAddress = new IpAddress;
                $ipAddress->setIpAddress(trim($address));
                $lead->addIpAddress($ipAddress);
            }
        }
        unset($fields['ip']);

        if (!empty($fields['points']) && !empty($data[$fields['points']]) && $lead->getId() === null) {
            // Add points only for new leads
            $lead->setPoints($data[$fields['points']]);

            //add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($data[$fields['points']]);
            $log->setLead($lead);
            $log->setType('lead');
            $log->setEventName($this->factory->getTranslator()->trans('mautic.lead.import.event.name'));
            $log->setActionName($this->factory->getTranslator()->trans('mautic.lead.import.action.name', array(
                '%name%' => $this->factory->getUser()->getUsername()
            )));
            $log->setIpAddress($this->factory->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);
        }
        unset($fields['points']);

        if (!empty($fields['doNotEmail']) && !empty($data[$fields['doNotEmail']]) && $hasEmail) {
            $doNotEmail = filter_var($data[$fields['doNotEmail']], FILTER_VALIDATE_BOOLEAN);
            if ($doNotEmail) {
                $reason = $this->factory->getTranslator()->trans('mautic.lead.import.by.user', array(
                    "%user%" => $this->factory->getUser()->getUsername()
                ));

                $this->setDoNotContact($lead, $data[$fields['email']], $reason, false);
            }
        }
        unset($fields['doNotEmail']);

        if ($owner !== null) {
            $lead->setOwner($this->em->getReference('MauticUserBundle:User', $owner));
        }

        if ($tags !== null) {
            $this->modifyTags($lead, $tags, null, false);
        }

        foreach ($fields as $leadField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data) && !is_null($data[$importField]) && $data[$importField] != '') {
                $lead->addUpdatedField($leadField, $data[$importField]);
            }
        }

        $lead->imported = true;

        if ($persist) {
            $this->saveEntity($lead);

            if ($list !== null) {
                $this->addToLists($lead, array($list));
            }
        }

        return $merged;
    }

    /**
     * Update a leads tags
     *
     * @param Lead  $lead
     * @param array $tags
     * @param bool|false $removeOrphans
     */
    public function setTags(Lead $lead, array $tags, $removeOrphans = false)
    {
        $currentTags  = $lead->getTags();
        $leadModified = $tagsDeleted = false;

        foreach ($currentTags as $tagName => $tag) {
            if (!in_array($tag->getId(), $tags)) {
                // Tag has been removed
                $lead->removeTag($tag);
                $leadModified = $tagsDeleted = true;
            } else {
                // Remove tag so that what's left are new tags
                $key = array_search($tag->getId(), $tags);
                unset($tags[$key]);
            }
        }

        if (!empty($tags)) {
            foreach($tags as $tag) {
                if (is_numeric($tag)) {
                    // Existing tag being added to this lead
                    $lead->addTag(
                        $this->factory->getEntityManager()->getReference('MauticLeadBundle:Tag', $tag)
                    );
                } else {
                    // New tag
                    $newTag = new Tag();
                    $newTag->setTag(InputHelper::clean($tag));
                    $lead->addTag($newTag);
                }
            }
            $leadModified = true;
        }

        if ($leadModified) {
            $this->saveEntity($lead);

            // Delete orphaned tags
            if ($tagsDeleted && $removeOrphans) {
                $this->getTagRepository()->deleteOrphans();
            }
        }
    }

    /**
     * Modify tags with support to remove via a prefixed minus sign
     *
     * @param Lead $lead
     * @param      $tags
     * @param      $removeTags
     * @param      $persist
     */
    public function modifyTags(Lead $lead, $tags, array $removeTags = null, $persist = true)
    {
        $leadTags = $lead->getTags();

        if (!is_array($tags)) {
            $tags = explode(',', $tags);
        }

        array_walk($tags, create_function('&$val', '$val = trim($val); \Mautic\CoreBundle\Helper\InputHelper::clean($val);'));

        // See which tags already exist
        $foundTags = $this->getTagRepository()->getTagsByName($tags);
        foreach ($tags as $tag) {
            if (strpos($tag, '-') === 0) {
                // Tag to be removed
                $tag = substr($tag, 1);

                if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                    $lead->removeTag($foundTags[$tag]);
                }
            } else {
                // Tag to be added
                if (!array_key_exists($tag, $foundTags)) {
                    // New tag
                    $newTag = new Tag();
                    $newTag->setTag($tag);
                    $lead->addTag($newTag);
                } elseif (!$leadTags->contains($foundTags[$tag])) {
                    $lead->addTag($foundTags[$tag]);
                }
            }
        }

        if ($removeTags !== null) {
            foreach ($removeTags as $tag) {
                // Tag to be removed
                if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                    $lead->removeTag($foundTags[$tag]);
                }
            }
        }

        if ($persist) {
            $this->saveEntity($lead);
        }
    }

    /**
     * Get array of available lead tags
     */
    public function getTagList()
    {
        return $this->getTagRepository()->getSimpleList(null, array(), 'tag', 'id');
    }
}
