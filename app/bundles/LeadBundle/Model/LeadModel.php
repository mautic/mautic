<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use DeviceDetector\DeviceDetector;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyChangeLog;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Event\CategoryChangeEvent;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Intl\Intl;

/**
 * Class LeadModel
 * {@inheritdoc}
 */
class LeadModel extends FormModel
{
    use DefaultValueTrait, OperatorListTrait, RequestTrait;

    private $currentLead       = null;
    private $systemCurrentLead = null;

    const CHANNEL_FEATURE = 'contact_preference';

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var array
     */
    protected $leadFields = [];

    /**
     * @var ListModel
     */
    protected $leadListModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var CategoryModel
     */
    protected $categoryModel;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var ChannelListHelper
     */
    protected $channelListHelper;

    /**
     * @var bool
     */
    protected $trackByIp = false;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * LeadModel constructor.
     *
     * @param RequestStack         $requestStack
     * @param CookieHelper         $cookieHelper
     * @param IpLookupHelper       $ipLookupHelper
     * @param PathsHelper          $pathsHelper
     * @param IntegrationHelper    $integrationHelper
     * @param FieldModel           $leadFieldModel
     * @param ListModel            $leadListModel
     * @param FormFactory          $formFactory
     * @param CompanyModel         $companyModel
     * @param CategoryModel        $categoryModel
     * @param ChannelListHelper    $channelListHelper
     * @param                      $trackByIp
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(
        RequestStack $requestStack,
        CookieHelper $cookieHelper,
        IpLookupHelper $ipLookupHelper,
        PathsHelper $pathsHelper,
        IntegrationHelper $integrationHelper,
        FieldModel $leadFieldModel,
        ListModel $leadListModel,
        FormFactory $formFactory,
        CompanyModel $companyModel,
        CategoryModel $categoryModel,
        ChannelListHelper $channelListHelper,
        $trackByIp,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->request              = $requestStack->getCurrentRequest();
        $this->cookieHelper         = $cookieHelper;
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->pathsHelper          = $pathsHelper;
        $this->integrationHelper    = $integrationHelper;
        $this->leadFieldModel       = $leadFieldModel;
        $this->leadListModel        = $leadListModel;
        $this->companyModel         = $companyModel;
        $this->formFactory          = $formFactory;
        $this->categoryModel        = $categoryModel;
        $this->channelListHelper    = $channelListHelper;
        $this->trackByIp            = $trackByIp;
        $this->coreParametersHelper = $coreParametersHelper;
    }

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
            $fields = $this->leadFieldModel->getFieldList(true, false);

            $socialFields = (!empty($fields['social'])) ? array_keys($fields['social']) : [];
            $repo->setAvailableSocialFields($socialFields);

            $searchFields = [];
            foreach ($fields as $group => $groupFields) {
                $searchFields = array_merge($searchFields, array_keys($groupFields));
            }
            $repo->setAvailableSearchFields($searchFields);
        }

        return $repo;
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\TagRepository
     */
    public function getTagRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Tag');
    }

    /**
     * @return \Mautic\LeadBundle\Entity\PointsChangeLogRepository
     */
    public function getPointLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:PointsChangeLog');
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\UtmTagRepository
     */
    public function getUtmTagRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:UtmTag');
    }

    /**
     * Get the tags repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadDeviceRepository
     */
    public function getDeviceRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadDevice');
    }

    /**
     * Get the lead event log repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadEventLogRepository
     */
    public function getEventLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadEventLog');
    }

    /**
     * Get the frequency rules repository.
     *
     * @return \Mautic\LeadBundle\Entity\FrequencyRuleRepository
     */
    public function getFrequencyRuleRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:FrequencyRule');
    }

    /**
     * Get the Stages change log repository.
     *
     * @return \Mautic\LeadBundle\Entity\StagesChangeLogRepository
     */
    public function getStagesChangeLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:StagesChangeLog');
    }

    /**
     * Get the lead categories repository.
     *
     * @return \Mautic\LeadBundle\Entity\LeadCategoryRepository
     */
    public function getLeadCategoryRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadCategory');
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
        return 'getPrimaryIdentifier';
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
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('lead', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|Lead
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Lead();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Lead) {
            throw new MethodNotAllowedHttpException(['Lead'], 'Entity must be of class Lead()');
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::LEAD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::LEAD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::LEAD_PRE_DELETE;
                break;
            case 'post_delete':
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
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        $companyFieldMatches = [];
        $fields              = $entity->getFields();
        $company             = null;

        //check to see if we can glean information from ip address
        if (!$entity->imported && count($ips = $entity->getIpAddresses())) {
            $details = $ips->first()->getIpDetails();
            // Only update with IP details if none of the following are set to prevent wrong combinations
            if (empty($fields['core']['city']['value']) && empty($fields['core']['state']['value']) && empty($fields['core']['country']['value']) && empty($fields['core']['zipcode']['value'])) {
                if (!empty($details['city'])) {
                    $entity->addUpdatedField('city', $details['city']);
                    $companyFieldMatches['city'] = $details['city'];
                }

                if (!empty($details['region'])) {
                    $entity->addUpdatedField('state', $details['region']);
                    $companyFieldMatches['state'] = $details['region'];
                }

                if (!empty($details['country'])) {
                    $entity->addUpdatedField('country', $details['country']);
                    $companyFieldMatches['country'] = $details['country'];
                }

                if (!empty($details['zipcode'])) {
                    $entity->addUpdatedField('zipcode', $details['zipcode']);
                }
            }
        }

        $updatedFields = $entity->getUpdatedFields();
        if (isset($updatedFields['company'])) {
            $companyFieldMatches['company']            = $updatedFields['company'];
            list($company, $leadAdded, $companyEntity) = IdentifyCompanyHelper::identifyLeadsCompany($companyFieldMatches, $entity, $this->companyModel);
            if ($leadAdded) {
                $entity->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            }
        }

        $this->setEntityDefaultValues($entity);

        parent::saveEntity($entity, $unlock);

        if (!empty($company)) {
            // Save after the lead in for new leads created through the API and maybe other places
            $this->companyModel->addLeadToCompany($companyEntity, $entity, true);
            $this->em->detach($companyEntity);
        }
        $this->em->clear(CompanyChangeLog::class);
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        // Delete custom avatar if one exists
        $imageDir = $this->pathsHelper->getSystemPath('images', true);
        $avatar   = $imageDir.'/lead_avatars/avatar'.$entity->getId();

        if (file_exists($avatar)) {
            unlink($avatar);
        }

        parent::deleteEntity($entity);
    }

    /**
     * Populates custom field values for updating the lead. Also retrieves social media data.
     *
     * @param Lead       $lead
     * @param array      $data
     * @param bool|false $overwriteWithBlank
     * @param bool|true  $fetchSocialProfiles
     * @param bool|false $bindWithForm        Send $data through the Lead form and only use valid data (should be used with request data)
     *
     * @return array
     */
    public function setFieldValues(Lead &$lead, array $data, $overwriteWithBlank = false, $fetchSocialProfiles = true, $bindWithForm = false)
    {
        if ($fetchSocialProfiles) {
            //@todo - add a catch to NOT do social gleaning if a lead is created via a form, etc as we do not want the user to experience the wait
            //generate the social cache
            list($socialCache, $socialFeatureSettings) = $this->integrationHelper->getUserProfiles(
                $lead,
                $data,
                true,
                null,
                false,
                true
            );

            //set the social cache while we have it
            if (!empty($socialCache)) {
                $lead->setSocialCache($socialCache);
            }
        }

        if (isset($data['stage'])) {
            $stagesChangeLogRepo = $this->getStagesChangeLogRepository();
            $currentLeadStage    = $stagesChangeLogRepo->getCurrentLeadStage($lead->getId());

            if ($data['stage'] !== $currentLeadStage) {
                $stage = $this->em->getRepository('MauticStageBundle:Stage')->find($data['stage']);
                $lead->stageChangeLogEntry(
                    $stage,
                    $stage->getId().':'.$stage->getName(),
                    $this->translator->trans('mautic.stage.event.changed')
                );
            }
        }

        //save the field values
        $fieldValues = $lead->getFields();

        if (empty($fieldValues) || $bindWithForm) {
            // Lead is new or they haven't been populated so let's build the fields now
            static $flatFields, $fields;
            if (empty($flatFields)) {
                $flatFields = $this->leadFieldModel->getEntities(
                    [
                        'filter'         => ['isPublished' => true, 'object' => 'lead'],
                        'hydration_mode' => 'HYDRATE_ARRAY',
                    ]
                );
                $fields = $this->organizeFieldsByGroup($flatFields);
            }

            if (empty($fieldValues)) {
                $fieldValues = $fields;
            }
        }

        if ($bindWithForm) {
            // Cleanup the field values
            $form = $this->createForm(
                new Lead(), // use empty lead to prevent binding errors
                $this->formFactory,
                null,
                ['fields' => $flatFields, 'csrf_protection' => false, 'allow_extra_fields' => true]
            );

            // Unset stage and owner from the form because it's already been handled
            unset($data['stage'], $data['owner'], $data['tags']);
            // Prepare special fields
            $this->prepareParametersFromRequest($form, $data, $lead);
            // Submit the data
            $form->submit($data);

            if ($form->getErrors()->count()) {
                $this->logger->addDebug('LEAD: form validation failed with an error of '.(string) $form->getErrors());
            }
            foreach ($form as $field => $formField) {
                if (isset($data[$field])) {
                    if ($formField->getErrors()->count()) {
                        $this->logger->addDebug('LEAD: '.$field.' failed form validation with an error of '.(string) $formField->getErrors());
                        // Don't save bad data
                        unset($data[$field]);
                    } else {
                        $data[$field] = $formField->getData();
                    }
                }
            }
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

                    if (is_array($newValue)) {
                        $newValue = implode('|', $newValue);
                    }

                    if ($curValue !== $newValue && (strlen($newValue) > 0 || (strlen($newValue) === 0 && $overwriteWithBlank))) {
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
     * Disassociates a user from leads.
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
     * Get list of entities for autopopulate fields.
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @param $start
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0)
    {
        $results = [];
        switch ($type) {
            case 'user':
                $results = $this->em->getRepository('MauticUserBundle:User')->getUserList($filter, $limit, $start, ['lead' => 'leads']);
                break;
        }

        return $results;
    }

    /**
     * Obtain an array of users for api lead edits.
     *
     * @return mixed
     */
    public function getOwnerList()
    {
        $results = $this->em->getRepository('MauticUserBundle:User')->getUserList('', 0);

        return $results;
    }

    /**
     * Obtains a list of leads based off IP.
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
     * Gets the details of a lead if not already set.
     *
     * @param $lead
     *
     * @return mixed
     */
    public function getLeadDetails($lead)
    {
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
     * Reorganizes a field list to be keyed by field's group then alias.
     *
     * @param $fields
     *
     * @return array
     */
    public function organizeFieldsByGroup($fields)
    {
        $array = [];

        foreach ($fields as $field) {
            if ($field instanceof LeadField) {
                $alias = $field->getAlias();
                if ($field->isPublished() and $field->getObject() === 'Lead') {
                    $group                          = $field->getGroup();
                    $array[$group][$alias]['id']    = $field->getId();
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field->getLabel();
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field->getType();
                }
            } else {
                $alias = $field['alias'];
                if ($field['isPublished'] and $field['object'] === 'lead') {
                    $group                          = $field['group'];
                    $array[$group][$alias]['id']    = $field['id'];
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field['label'];
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field['type'];
                }
            }
        }

        //make sure each group key is present
        $groups = ['core', 'social', 'personal', 'professional'];
        foreach ($groups as $g) {
            if (!isset($array[$g])) {
                $array[$g] = [];
            }
        }

        return $array;
    }

    /**
     * Takes leads organized by group and flattens them into just alias => value.
     *
     * @param $fields
     *
     * @deprecated 2.0 to be removed in 3.0 - Use the Lead entity's getProfileFields() instead
     *
     * @return array
     */
    public function flattenFields($fields)
    {
        $flat = [];
        foreach ($fields as $group => $fields) {
            foreach ($fields as $field) {
                $flat[$field['alias']] = $field['value'];
            }
        }

        return $flat;
    }

    /**
     * Returns flat array for single lead.
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
     * was just generated or not.
     *
     * @param bool|false $returnTracking
     *
     * @return Lead|array
     */
    public function getCurrentLead($returnTracking = false)
    {
        if ((!$returnTracking && $this->systemCurrentLead) || defined('IN_MAUTIC_CONSOLE')) {
            // Just return the system set lead
            if (null === $this->systemCurrentLead) {
                $this->systemCurrentLead = new Lead();
            }

            return $this->systemCurrentLead;
        }

        if ($this->request) {
            $this->logger->addDebug('LEAD: Tracking session for '.$this->request->getMethod().' '.$this->request->getRequestUri());
        }
        list($trackingId, $generated) = $this->getTrackingCookie();
        $this->logger->addDebug("LEAD: Tracking ID for this contact is {$trackingId} (".(int) $generated.')');

        if (empty($this->currentLead)) {
            $ip = $this->ipLookupHelper->getIpAddress();
            if ($this->request && !$leadId = $this->request->cookies->get($trackingId)) {
                $leadId = ('GET' == $this->request->getMethod())
                    ?
                    $this->request->query->get('mtc_id')
                    :
                    $this->request->request->get('mtc_id');
            }

            // if no trackingId cookie set the lead is not tracked yet so create a new one
            if (empty($leadId)) {
                if (!$ip->isTrackable()) {
                    // Don't save leads that are from a non-trackable IP by default
                    $lead = $this->createNewContact($ip, false);
                } else {
                    $lead = null;
                    if ($this->trackByIp) {
                        // Try looking up the contact by it's IP address
                        $leads = $this->getLeadsByIp($ip->getIpAddress());

                        if (count($leads)) {
                            $lead = $leads[0];
                            $this->logger->addDebug("LEAD: Existing lead found with ID# {$lead->getId()}.");
                        }
                    }

                    if (!$lead) {
                        //let's create a lead
                        $lead = $this->createNewContact($ip);
                    }
                }
            } else {
                $lead = $this->getEntity($leadId);

                if ($lead === null) {
                    $lead = $this->createNewContact($ip);
                } else {
                    $this->logger->addDebug("LEAD: Existing lead found with ID# $leadId.");
                }
            }

            $this->currentLead = $lead;
            if ($leadId = $lead->getId()) {
                $this->setLeadCookie($leadId);
            }
        }

        // Log last active
        if (!defined('MAUTIC_LEAD_LASTACTIVE_LOGGED')) {
            $this->getRepository()->updateLastActive($this->currentLead->getId());
            define('MAUTIC_LEAD_LASTACTIVE_LOGGED', 1);
        }

        return ($returnTracking) ? [$this->currentLead, $trackingId, $generated] : $this->currentLead;
    }

    /**
     * Get the lead from request (ct/clickthrough) and handles auto merging of lead data from request parameters.
     *
     * @deprecated - here till all lead methods are converted to contact methods; preferably use getContactFromRequest instead
     *
     * @param array $queryFields
     *
     * @return array|Lead|null
     */
    public function getLeadFromRequest(array $queryFields = [])
    {
        return $this->getContactFromRequest($queryFields);
    }

    /**
     * Get the contat from request (ct/clickthrough) and handles auto merging of contact data from request parameters.
     *
     * @param array $queryFields
     *
     * @return array|Lead|null
     */
    public function getContactFromRequest($queryFields = [], $trackByFingerprint = false)
    {
        $lead = null;

        $ipAddress = $this->ipLookupHelper->getIpAddress();

        // Check for a lead requested through clickthrough query parameter
        if (isset($queryFields['ct'])) {
            $clickthrough = $queryFields['ct'];
        } elseif ($clickthrough = $this->request->get('ct', [])) {
            $clickthrough = $this->decodeArrayFromUrl($clickthrough);
        }

        if (is_array($clickthrough) && !empty($clickthrough['lead'])) {
            $lead = $this->getEntity($clickthrough['lead']);
            // identify contact from link
            if ($this->coreParametersHelper->getParameter('track_by_tracking_url') && !isset($queryFields['email']) && $lead && $email = $lead->getEmail()) {
                $queryFields['email'] = $email;
            }
            $this->logger->addDebug("LEAD: Contact ID# {$clickthrough['lead']} tracked through clickthrough query.");
        }

        // First determine if this request is already tracked as a specific lead
        list($trackingId, $generated) = $this->getTrackingCookie();

        if (!$leadId = $this->request->cookies->get($trackingId)) {
            $leadId = ('GET' == $this->request->getMethod())
                ?
                $this->request->query->get('mtc_id')
                :
                $this->request->request->get('mtc_id');
        }

        if ($leadId && $lead = $this->getEntity($leadId)) {
            $this->logger->addDebug("LEAD: Contact ID# {$leadId} tracked through tracking ID ($trackingId}.");
        }

        // Search for lead by request and/or update lead fields if some data were sent in the URL query
        $availableLeadFields = $this->leadFieldModel->getFieldList(
            false,
            false,
            [
                'isPublished'         => true,
                'isPubliclyUpdatable' => true,
            ]
        );

        $uniqueLeadFields    = $this->leadFieldModel->getUniqueIdentiferFields();
        $uniqueLeadFieldData = [];
        $inQuery             = array_intersect_key($queryFields, $availableLeadFields);
        foreach ($inQuery as $k => $v) {
            if (empty($queryFields[$k])) {
                unset($inQuery[$k]);
            }

            if (array_key_exists($k, $uniqueLeadFields)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        if (count($inQuery)) {
            // Check for leads using unique identifier
            if (count($uniqueLeadFieldData)) {
                $existingLeads = $this->getRepository()->getLeadsByUniqueFields($uniqueLeadFieldData, ($lead) ? $lead->getId() : null);

                if (!empty($existingLeads)) {
                    $this->logger->addDebug("LEAD: Existing contact ID# {$existingLeads[0]->getId()} found through query identifiers.");
                    // Merge with existing lead or use the one found
                    $lead = ($lead) ? $this->mergeLeads($lead, $existingLeads[0]) : $existingLeads[0];
                }
            }
        }

        // Search for lead by fingerprint
        if (empty($lead) && !empty($queryFields['fingerprint']) && $trackByFingerprint) {
            $deviceRepo = $this->getDeviceRepository();
            $device     = $deviceRepo->getDeviceByFingerprint($queryFields['fingerprint']);
            if ($device) {
                $lead = $this->getEntity($device['lead_id']);
            }
        }

        if (empty($lead)) {
            // No lead found so generate one
            $lead = $this->getCurrentLead();
        }

        $leadIpAddresses = $lead->getIpAddresses();
        if (!$leadIpAddresses->contains($ipAddress)) {
            $lead->addIpAddress($ipAddress);
        }

        $this->setFieldValues($lead, $inQuery, false, true, true);

        if (isset($queryFields['tags'])) {
            $this->modifyTags($lead, $queryFields['tags']);
        }

        $this->setCurrentLead($lead);

        return $lead;
    }

    /**
     * Sets current lead.
     *
     * @param Lead $lead
     */
    public function setCurrentLead(Lead $lead)
    {
        $this->logger->addDebug("LEAD: {$lead->getId()} set as current lead.");

        if ($this->systemCurrentLead || defined('IN_MAUTIC_CONSOLE')) {
            // Overwrite system current lead
            $this->systemCurrentLead = $lead;

            return;
        }

        $oldLead = (is_null($this->currentLead)) ? null : $this->currentLead;

        $fields = $lead->getFields();
        if (empty($fields)) {
            $lead->setFields($this->getLeadDetails($lead));
        }

        $this->currentLead = $lead;

        // Set last active
        $this->currentLead->setLastActive(new \DateTime());

        if ($lead->getId()) {
            // Update tracking cookies if the lead is different
            if ($oldLead && $oldLead->getId() != $lead->getId()) {
                list($newTrackingId, $oldTrackingId) = $this->getTrackingCookie(true);
                $this->logger->addDebug(
                    "LEAD: Tracking code changed from $oldTrackingId for contact ID# {$oldLead->getId()} to $newTrackingId for contact ID# {$lead->getId()}"
                );

                //set the tracking cookies
                $this->setLeadCookie($lead->getId());

                if ($oldTrackingId && $oldLead) {
                    if ($this->dispatcher->hasListeners(LeadEvents::CURRENT_LEAD_CHANGED)) {
                        $event = new LeadChangeEvent($oldLead, $oldTrackingId, $lead, $newTrackingId);
                        $this->dispatcher->dispatch(LeadEvents::CURRENT_LEAD_CHANGED, $event);
                    }
                }
            } elseif (!$oldLead) {
                // New lead, set the tracking cookie
                $this->setLeadCookie($lead->getId(), true);
            }
        }
    }

    /**
     * Used by system processes that hook into events that use getCurrentLead().
     *
     * @param Lead $lead
     */
    public function setSystemCurrentLead(Lead $lead = null)
    {
        $fields = $lead->getFields();
        if (empty($fields)) {
            $lead->setFields($this->getLeadDetails($lead));
        }

        $this->systemCurrentLead = $lead;
    }

    /**
     * Get a list of segments this lead belongs to.
     *
     * @param Lead $lead
     * @param bool $forLists
     * @param bool $arrayHydration
     * @param bool $isPublic
     *
     * @return mixed
     */
    public function getLists(Lead $lead, $forLists = false, $arrayHydration = false, $isPublic = false)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:LeadList');

        return $repo->getLeadLists($lead->getId(), $forLists, $arrayHydration, $isPublic);
    }

    /**
     * Get a list of companies this contact belongs to.
     *
     * @param Lead $lead
     *
     * @return mixed
     */
    public function getCompanies(Lead $lead)
    {
        $repo = $this->em->getRepository('MauticLeadBundle:CompanyLead');

        return $repo->getCompaniesByLeadId($lead->getId());
    }

    /**
     * Get or generate the tracking ID for the current session.
     *
     * @param bool|false $forceRegeneration
     *
     * @return array
     */
    public function getTrackingCookie($forceRegeneration = false)
    {
        static $trackingId = false, $generated = false;

        if ($forceRegeneration) {
            $generated = true;

            $oldTrackingId = $this->request->cookies->get('mautic_session_id');
            $trackingId    = hash('sha1', uniqid(mt_rand()));

            //create a tracking cookie with a expire of two years
            $this->cookieHelper->setCookie('mautic_session_id', $trackingId, 31536000);

            return [$trackingId, $oldTrackingId];
        }

        if (empty($trackingId)) {
            //check for the tracking cookie or sid from query
            if ($this->request && !$trackingId = $this->request->cookies->get('mautic_session_id')) {
                $trackingId = ('GET' == $this->request->getMethod())
                    ?
                    $this->request->query->get('mtc_sid')
                    :
                    $this->request->request->get('mtc_sid');
            }
            $generated = false;
            if (empty($trackingId)) {
                $trackingId = hash('sha1', uniqid(mt_rand()));
                $generated  = true;
            }

            //create a tracking cookie with a expire of two years
            $this->cookieHelper->setCookie('mautic_session_id', $trackingId, 31536000);
        }

        return [$trackingId, $generated];
    }

    /**
     * Sets the leadId for the current session.
     *
     * @param $leadId
     */
    public function setLeadCookie($leadId)
    {
        // Remove the old if set
        $oldTrackingId                = $this->request->cookies->get('mautic_session_id');
        list($trackingId, $generated) = $this->getTrackingCookie();

        if ($generated && $oldTrackingId) {
            $this->cookieHelper->setCookie($oldTrackingId, null, -3600);
        }

        // Create a cookie with a expire of two years
        $this->cookieHelper->setCookie($trackingId, $leadId, 31536000);
    }

    /**
     * Add lead to lists.
     *
     * @param array|Lead     $lead
     * @param array|LeadList $lists
     * @param bool           $manuallyAdded
     */
    public function addToLists($lead, $lists, $manuallyAdded = true)
    {
        $this->leadListModel->addLead($lead, $lists, $manuallyAdded);
    }

    /**
     * Remove lead from lists.
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyRemoved
     */
    public function removeFromLists($lead, $lists, $manuallyRemoved = true)
    {
        $this->leadListModel->removeLead($lead, $lists, $manuallyRemoved);
    }
    /**
     * Add lead to Stage.
     *
     * @param array|Lead  $lead
     * @param array|Stage $stage
     * @param bool        $manuallyAdded
     *
     * @return $this
     */
    public function addToStages($lead, $stage, $manuallyAdded = true)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }
        $lead->setStage($stage);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.added.batch')
        );

        return $this;
    }

    /**
     * Remove lead from Stage.
     *
     * @param      $lead
     * @param      $stage
     * @param bool $manuallyRemoved
     *
     * @return $this
     */
    public function removeFromStages($lead, $stage, $manuallyRemoved = true)
    {
        $lead->setStage(null);
        $lead->stageChangeLogEntry(
            $stage,
            $stage->getId().': '.$stage->getName(),
            $this->translator->trans('mautic.stage.event.removed.batch')
        );

        return $this;
    }

    /**
     * Merge two leads; if a conflict of data occurs, the newest lead will get precedence.
     *
     * @param Lead $lead
     * @param Lead $lead2
     * @param bool $autoMode If true, the newest lead will be merged into the oldes then deleted; otherwise, $lead will be merged into $lead2 then deleted
     *
     * @return Lead
     */
    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true)
    {
        $this->logger->debug('LEAD: Merging leads');

        $leadId  = $lead->getId();
        $lead2Id = $lead2->getId();

        //if they are the same lead, then just return one
        if ($leadId === $lead2Id) {
            $this->logger->debug('LEAD: Leads are the same');

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
        $this->logger->debug('LEAD: Lead ID# '.$mergeFrom->getId().' will be merged into ID# '.$mergeWith->getId());

        //dispatch pre merge event
        $event = new LeadMergeEvent($mergeWith, $mergeFrom);
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_PRE_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);
        }

        //merge IP addresses
        $ipAddresses = $mergeFrom->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $mergeWith->addIpAddress($ip);

            $this->logger->debug('LEAD: Associating with IP '.$ip->getIpAddress());
        }

        //merge fields
        $mergeFromFields = $mergeFrom->getFields();
        foreach ($mergeFromFields as $group => $groupFields) {
            foreach ($groupFields as $alias => $details) {
                //overwrite old lead's data with new lead's if new lead's is not empty
                if (!empty($details['value'])) {
                    $mergeWith->addUpdatedField($alias, $details['value']);

                    $this->logger->debug('LEAD: Updated '.$alias.' = '.$details['value']);
                }
            }
        }

        //merge owner
        $oldOwner = $mergeWith->getOwner();
        $newOwner = $mergeFrom->getOwner();

        if ($oldOwner === null && $newOwner !== null) {
            $mergeWith->setOwner($newOwner);

            $this->logger->debug('LEAD: New owner is '.$newOwner->getId());
        }

        //sum points
        $mergeWithPoints = $mergeWith->getPoints();
        $mergeFromPoints = $mergeFrom->getPoints();
        $mergeWith->setPoints($mergeWithPoints + $mergeFromPoints);
        $this->logger->debug('LEAD: Adding '.$mergeFromPoints.' points to lead');

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
     * @param Lead   $lead
     * @param string $channel
     *
     * @return int
     *
     * @see \Mautic\LeadBundle\Entity\DoNotContact This method can return boolean false, so be
     *                                             sure to always compare the return value against
     *                                             the class constants of DoNotContact
     */
    public function isContactable(Lead $lead, $channel)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository('MauticLeadBundle:DoNotContact');

        /** @var \Mautic\LeadBundle\Entity\DoNotContact[] $entries */
        $dncEntries = $dncRepo->getEntriesByLeadAndChannel($lead, $channel);

        // If the lead has no entries in the DNC table, we're good to go
        if (empty($dncEntries)) {
            return DoNotContact::IS_CONTACTABLE;
        }

        foreach ($dncEntries as $dnc) {
            if ($dnc->getReason() !== DoNotContact::IS_CONTACTABLE) {
                return $dnc->getReason();
            }
        }

        return DoNotContact::IS_CONTACTABLE;
    }

    /**
     * Remove a Lead's DNC entry based on channel.
     *
     * @param Lead      $lead
     * @param string    $channel
     * @param bool|true $persist
     *
     * @return bool
     */
    public function removeDncForLead(Lead $lead, $channel, $persist = true)
    {
        /** @var DoNotContact $dnc */
        foreach ($lead->getDoNotContact() as $dnc) {
            if ($dnc->getChannel() === $channel) {
                $lead->removeDoNotContactEntry($dnc);

                if ($persist) {
                    $this->saveEntity($lead);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Create a DNC entry for a lead.
     *
     * @param Lead         $lead
     * @param string|array $channel            If an array with an ID, use the structure ['email' => 123]
     * @param string       $comments
     * @param int          $reason             Must be a class constant from the DoNotContact class
     * @param bool         $persist
     * @param bool         $checkCurrentStatus
     *
     * @return bool|DoNotContact If a DNC entry is added or updated, returns the DoNotContact object. If a DNC is already present
     *                           and has the specified reason, nothing is done and this returns false
     */
    public function addDncForLead(Lead $lead, $channel, $comments = '', $reason = DoNotContact::BOUNCED, $persist = true, $checkCurrentStatus = true)
    {
        // if !$checkCurrentStatus, assume is contactable due to already being valided
        $isContactable = ($checkCurrentStatus) ? $this->isContactable($lead, $channel) : DoNotContact::IS_CONTACTABLE;

        // If they don't have a DNC entry yet
        if ($isContactable === DoNotContact::IS_CONTACTABLE) {
            $dnc = new DoNotContact();

            if (is_array($channel)) {
                $channelId = reset($channel);
                $channel   = key($channel);

                $dnc->setChannelId((int) $channelId);
            }

            $dnc->setChannel($channel);
            $dnc->setReason($reason);
            $dnc->setLead($lead);
            $dnc->setDateAdded(new \DateTime());
            $dnc->setComments($comments);

            $lead->addDoNotContactEntry($dnc);

            if ($persist) {
                // Use model saveEntity to trigger events for DNC change
                $this->saveEntity($lead);
            }

            return $dnc;
        }
        // Or if the given reason is different than the stated reason
        elseif ($isContactable !== $reason) {
            /** @var DoNotContact $dnc */
            foreach ($lead->getDoNotContact() as $dnc) {
                // Only update if the contact did not unsubscribe themselves
                if ($dnc->getChannel() === $channel && $dnc->getReason() !== DoNotContact::UNSUBSCRIBED) {
                    // Remove the outdated entry
                    $lead->removeDoNotContactEntry($dnc);

                    // Update the DNC entry
                    $dnc->setChannel($channel);
                    $dnc->setReason($reason);
                    $dnc->setLead($lead);
                    $dnc->setDateAdded(new \DateTime());
                    $dnc->setComments($comments);

                    // Re-add the entry to the lead
                    $lead->addDoNotContactEntry($dnc);

                    if ($persist) {
                        // Use model saveEntity to trigger events for DNC change
                        $this->saveEntity($lead);
                    }

                    return $dnc;
                }
            }
        }

        return false;
    }

    /**
     * @depreacated 2.6.0 to be removed in 3.0; use getFrequencyRules() instead
     *
     * @param Lead $lead
     * @param null $channel
     *
     * @return mixed
     */
    public function getFrequencyRule(Lead $lead, $channel = null)
    {
        return $this->getFrequencyRules($lead, $channel);
    }

    /**
     * @param Lead   $lead
     * @param string $channel
     *
     * @return mixed
     */
    public function getFrequencyRules(Lead $lead, $channel = null)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\FrequencyRuleRepository $frequencyRuleRepo */
        $frequencyRuleRepo = $this->em->getRepository('MauticLeadBundle:FrequencyRule');
        $frequencyRules    = $frequencyRuleRepo->getFrequencyRules($channel, $lead->getId());

        if (empty($frequencyRules)) {
            return [];
        }

        return $frequencyRules;
    }

    /**
     * Set frequency rules for lead per channel.
     *
     * @param Lead $lead
     * @param null $data
     * @param null $leadLists
     *
     * @return bool Returns true
     */
    public function setFrequencyRules(Lead $lead, $data = null, $leadLists = null, $persist = true)
    {
        // One query to get all the lead's current frequency rules and go ahead and create entities for them
        $frequencyRules = $lead->getFrequencyRules()->toArray();
        $entities       = [];
        $channels       = $this->getPreferenceChannels();

        foreach ($channels as $ch) {
            if (empty($data['preferred_channel'])) {
                $data['preferred_channel'] = $ch;
            }

            $frequencyRule = (isset($frequencyRules[$ch])) ? $frequencyRules[$ch] : new FrequencyRule();
            $frequencyRule->setChannel($ch);
            $frequencyRule->setLead($lead);
            $frequencyRule->setDateAdded(new \DateTime());

            if (!empty($data['frequency_number_'.$ch]) && !empty($data['frequency_time_'.$ch])) {
                $frequencyRule->setFrequencyNumber($data['frequency_number_'.$ch]);
                $frequencyRule->setFrequencyTime($data['frequency_time_'.$ch]);
            } else {
                $frequencyRule->setFrequencyNumber(null);
                $frequencyRule->setFrequencyTime(null);
            }

            $frequencyRule->setPauseFromDate(!empty($data['contact_pause_start_date_'.$ch]) ? $data['contact_pause_start_date_'.$ch] : null);
            $frequencyRule->setPauseToDate(!empty($data['contact_pause_end_date_'.$ch]) ? $data['contact_pause_end_date_'.$ch] : null);

            $frequencyRule->setLead($lead);
            $frequencyRule->setPreferredChannel($data['preferred_channel'] === $ch);

            if ($persist) {
                $entities[$ch] = $frequencyRule;
            } else {
                $lead->addFrequencyRule($frequencyRule);
            }
        }

        if (!empty($entities)) {
            $this->em->getRepository('MauticLeadBundle:FrequencyRule')->saveEntities($entities);
        }

        foreach ($data['lead_lists'] as $leadList) {
            if (!isset($leadLists[$leadList])) {
                $this->addToLists($lead, [$leadList]);
            }
        }
        // Delete lists that were removed
        $deletedLists = array_diff(array_keys($leadLists), $data['lead_lists']);
        if (!empty($deletedLists)) {
            $this->removeFromLists($lead, $deletedLists);
        }

        if (!empty($data['global_categories'])) {
            $this->addToCategory($lead, $data['global_categories']);
        }
        $leadCategories = $this->getLeadCategories($lead);
        // Delete categories that were removed
        $deletedCategories = array_diff($leadCategories, $data['global_categories']);

        if (!empty($deletedCategories)) {
            $this->removeFromCategories($deletedCategories);
        }

        // Delete channels that were removed
        $deleted = array_diff_key($frequencyRules, $entities);
        if (!empty($deleted)) {
            $this->em->getRepository('MauticLeadBundle:FrequencyRule')->deleteEntities($deleted);
        }

        return true;
    }

    /**
     * @param Lead $lead
     * @param $categories
     * @param bool $manuallyAdded
     *
     * @return array
     */
    public function addToCategory(Lead $lead, $categories, $manuallyAdded = true)
    {
        $leadCategories = $this->getLeadCategoryRepository()->getLeadCategories($lead);

        $results = [];
        foreach ($categories as $category) {
            if (!isset($leadCategories[$category])) {
                $newLeadCategory = new LeadCategory();
                $newLeadCategory->setLead($lead);
                if (!$category instanceof Category) {
                    $category = $this->categoryModel->getEntity($category);
                }
                $newLeadCategory->setCategory($category);
                $newLeadCategory->setDateAdded(new \DateTime());
                $newLeadCategory->setManuallyAdded($manuallyAdded);
                $results[$category->getId()] = $newLeadCategory;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($lead, $category));
                }
            }
        }
        if (!empty($results)) {
            $this->getLeadCategoryRepository()->saveEntities($results);
        }

        return $results;
    }

    /**
     * @param $categories
     */
    public function removeFromCategories($categories)
    {
        $deleteCats = [];
        if (is_array($categories)) {
            foreach ($categories as $key => $category) {
                /** @var LeadCategory $category */
                $category     = $this->getLeadCategoryRepository()->getEntity($key);
                $deleteCats[] = $category;

                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                    $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($category->getLead(), $category->getCategory(), false));
                }
            }
        } elseif ($categories instanceof LeadCategory) {
            $deleteCats[] = $categories;

            if ($this->dispatcher->hasListeners(LeadEvents::LEAD_CATEGORY_CHANGE)) {
                $this->dispatcher->dispatch(LeadEvents::LEAD_CATEGORY_CHANGE, new CategoryChangeEvent($categories->getLead(), $categories->getCategory(), false));
            }
        }

        if (!empty($deleteCats)) {
            $this->getLeadCategoryRepository()->deleteEntities($deleteCats);
        }
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getLeadCategories(Lead $lead)
    {
        $leadCategories   = $this->getLeadCategoryRepository()->getLeadCategories($lead);
        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $leadCategoryList[$category['id']] = $category['category_id'];
        }

        return $leadCategoryList;
    }

    /**
     * @param array        $fields
     * @param array        $data
     * @param null         $owner
     * @param null         $list
     * @param null         $tags
     * @param bool         $persist
     * @param LeadEventLog $eventLog
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function importLead($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null)
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
            $userRepo      = $this->em->getRepository('MauticUserBundle:User');
            $createdByUser = $userRepo->findByIdentifier($data[$fields['createdByUser']]);
            if ($createdByUser !== null) {
                $lead->setCreatedBy($createdByUser);
            }
        }
        unset($fields['createdByUser']);

        if (!empty($fields['modifiedByUser']) && !empty($data[$fields['modifiedByUser']])) {
            $userRepo       = $this->em->getRepository('MauticUserBundle:User');
            $modifiedByUser = $userRepo->findByIdentifier($data[$fields['modifiedByUser']]);
            if ($modifiedByUser !== null) {
                $lead->setModifiedBy($modifiedByUser);
            }
        }
        unset($fields['modifiedByUser']);

        if (!empty($fields['ip']) && !empty($data[$fields['ip']])) {
            $addresses = explode(',', $data[$fields['ip']]);
            foreach ($addresses as $address) {
                $ipAddress = new IpAddress();
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
            $log->setEventName($this->translator->trans('mautic.lead.import.event.name'));
            $log->setActionName($this->translator->trans('mautic.lead.import.action.name', [
                '%name%' => $this->userHelper->getUser()->getUsername(),
            ]));
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);
        }

        if (!empty($fields['stage']) && !empty($data[$fields['stage']])) {
            static $stages = [];
            $stageName     = $data[$fields['stage']];
            if (!array_key_exists($stageName, $stages)) {
                // Set stage for contact
                $stage = $this->em->getRepository('MauticStageBundle:Stage')->getStageByName($stageName);

                if (empty($stage)) {
                    $stage = new Stage();
                    $stage->setName($stageName);
                    $stages[$stageName] = $stage;
                }
            } else {
                $stage = $stages[$stageName];
            }

            $lead->setStage($stage);

            //add a contact stage change log
            $log = new StagesChangeLog();
            $log->setStage($stage);
            $log->setEventName($stage->getId().':'.$stage->getName());
            $log->setLead($lead);
            $log->setActionName(
                $this->translator->trans(
                    'mautic.stage.import.action.name',
                    [
                        '%name%' => $this->userHelper->getUser()->getUsername(),
                    ]
                )
            );
            $log->setDateAdded(new \DateTime());
            $lead->stageChangeLog($log);
        }
        unset($fields['stage']);

        // Set unsubscribe status
        if (!empty($fields['doNotEmail']) && !empty($data[$fields['doNotEmail']]) && $hasEmail) {
            $doNotEmail = filter_var($data[$fields['doNotEmail']], FILTER_VALIDATE_BOOLEAN);
            if ($doNotEmail) {
                $reason = $this->translator->trans('mautic.lead.import.by.user', [
                    '%user%' => $this->userHelper->getUser()->getUsername(),
                ]);

                // The email must be set for successful unsubscribtion
                $lead->addUpdatedField('email', $data[$fields['email']]);
                $this->addDncForLead($lead, 'email', $reason, DoNotContact::MANUAL);
            }
        }
        unset($fields['doNotEmail']);

        if ($owner !== null) {
            $lead->setOwner($this->em->getReference('MauticUserBundle:User', $owner));
        }

        if ($tags !== null) {
            $this->modifyTags($lead, $tags, null, false);
        }

        // Set profile data using the form so that values are validated
        $fieldData = [];
        foreach ($fields as $leadField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data) && !is_null($data[$importField]) && $data[$importField] != '') {
                $fieldData[$leadField] = $data[$importField];
            }
        }

        if (empty($this->leadFields)) {
            $this->leadFields = $this->leadFieldModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'f.isPublished',
                                'expr'   => 'eq',
                                'value'  => true,
                            ],
                            [
                                'column' => 'f.object',
                                'expr'   => 'eq',
                                'value'  => 'lead',
                            ],
                        ],
                    ],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                ]
            );
        }

        $fieldErrors = [];

        foreach ($this->leadFields as $leadField) {
            if (isset($fieldData[$leadField['alias']])) {
                $fieldData[$leadField['alias']] = InputHelper::clean($fieldData[$leadField['alias']]);

                if ('NULL' === $fieldData[$leadField['alias']]) {
                    $fieldData[$leadField['alias']] = null;

                    continue;
                }

                try {
                    switch ($leadField['type']) {
                        // Adjust the boolean values from text to boolean
                        case 'boolean':
                            $fieldData[$leadField['alias']] = (int) filter_var($fieldData[$leadField['alias']], FILTER_VALIDATE_BOOLEAN);
                            break;
                        // Ensure date/time entries match what symfony expects
                        case 'datetime':
                        case 'date':
                        case 'time':
                            // Prevent zero based date placeholders
                            $dateTest = (int) str_replace(['/', '-', ' '], '', $fieldData[$leadField['alias']]);

                            if (!$dateTest) {
                                // Date placeholder was used so just ignore it to allow import of the field
                                unset($fieldData[$leadField['alias']]);
                            } else {
                                switch ($leadField['type']) {
                                    case 'datetime':
                                        $fieldData[$leadField['alias']] = (new \DateTime($fieldData[$leadField['alias']]))->format('Y-m-d H:i');
                                        break;
                                    case 'date':
                                        $fieldData[$leadField['alias']] = (new \DateTime($fieldData[$leadField['alias']]))->format('Y-m-d');
                                        break;
                                    case 'time':
                                        $fieldData[$leadField['alias']] = (new \DateTime($fieldData[$leadField['alias']]))->format('H:i');
                                        break;
                                }
                            }
                            break;
                        case 'multiselect':
                            if (strpos($fieldData[$leadField['alias']], '|') !== false) {
                                $fieldData[$leadField['alias']] = explode('|', $fieldData[$leadField['alias']]);
                            } else {
                                $fieldData[$leadField['alias']] = [$fieldData[$leadField['alias']]];
                            }
                            break;
                        case 'number':
                            $fieldData[$leadField['alias']] = (float) $fieldData[$leadField['alias']];
                            break;
                    }
                } catch (\Exception $exception) {
                    $fieldErrors[] = $leadField['alias'].': '.$exception->getMessage();
                }

                // Skip if the value is in the CSV row
                continue;
            } elseif ($leadField['defaultValue']) {

                // Fill in the default value if any
                $fieldData[$leadField['alias']] = ('multiselect' === $leadField['type']) ? [$leadField['defaultValue']] : $leadField['defaultValue'];
            }
        }

        if ($fieldErrors) {
            $fieldErrors = implode("\n", $fieldErrors);

            throw new \Exception($fieldErrors);
        }

        // All clear
        foreach ($fieldData as $field => $value) {
            $lead->addUpdatedField($field, $value);
        }

        $lead->imported = true;

        if ($eventLog) {
            $action = $merged ? 'updated' : 'inserted';
            $eventLog->setAction($action);
            $lead->addEventLog($eventLog);
        }

        if ($persist) {
            $this->saveEntity($lead);

            if ($list !== null) {
                $this->addToLists($lead, [$list]);
            }
        }

        return $merged;
    }

    /**
     * Update a leads tags.
     *
     * @param Lead       $lead
     * @param array      $tags
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
            foreach ($tags as $tag) {
                if (is_numeric($tag)) {
                    // Existing tag being added to this lead
                    $lead->addTag(
                        $this->em->getReference('MauticLeadBundle:Tag', $tag)
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
     * Update a leads UTM tags.
     *
     * @param Lead   $lead
     * @param UtmTag $utmTags
     */
    public function setUtmTags(Lead $lead, UtmTag $utmTags)
    {
        $lead->setUtmTags($utmTags);

        $this->saveEntity($lead);
    }

    /**
     * Add leads UTM tags via API.
     *
     * @param Lead  $lead
     * @param array $params
     */
    public function addUTMTags(Lead $lead, $params)
    {
        // known "synonym" fields expected
        $synonyms = ['useragent'  => 'user_agent',
                     'remotehost' => 'remote_host', ];

        // convert 'query' option to an array if necessary
        if (isset($params['query']) && !is_array($params['query'])) {
            // assume it's a query string; convert it to array
            parse_str($params['query'], $queryResult);
            if (!empty($queryResult)) {
                $params['query'] = $queryResult;
            } else {
                // Something wrong with, remove it
                unset($params['query']);
            }
        }

        // Fix up known synonym/mismatch field names
        foreach ($synonyms as $expected => $replace) {
            if (key_exists($expected, $params) && !isset($params[$replace])) {
                // add expected key name
                $params[$replace] = $params[$expected];
            }
        }

        // see if active date set, so we can use it
        $updateLastActive = false;
        $lastActive       = new \DateTime();
        // should be: yyyy-mm-ddT00:00:00+00:00
        if (isset($params['lastActive'])) {
            $lastActive       = new \DateTime($params['lastActive']);
            $updateLastActive = true;
        }
        $params['date_added'] = $lastActive;

        // New utmTag
        $utmTags = new UtmTag();

        // get available fields and their setter.
        $fields = $utmTags->getFieldSetterList();

        // cycle through calling appropriate setter
        foreach ($fields as $q => $setter) {
            if (isset($params[$q])) {
                $utmTags->$setter($params[$q]);
            }
        }

        // create device
        if (key_exists('useragent', $params) && !empty($params['useragent'])) {
            //device granularity
            $dd = new DeviceDetector($params['useragent']);
            $dd->parse();

            $deviceRepo = $this->getDeviceRepository();
            $device     = $deviceRepo->getDevice($lead, $dd->getDeviceName(), $dd->getBrand(), $dd->getModel());

            // Only if it does not exist already
            if (empty($device)) {
                $device = new LeadDevice();
                $device->setClientInfo($dd->getClient());
                $device->setDevice($dd->getDeviceName());
                $device->setDeviceBrand($dd->getBrand());
                $device->setDeviceModel($dd->getModel());
                $device->setDeviceOs($dd->getOs());
                $device->setDateAdded($lastActive);
                $device->setLead($lead);
                $this->getDeviceRepository()->saveEntity($device);
            }
        }

        // add the lead
        $utmTags->setLead($lead);
        if ($updateLastActive) {
            $lead->setLastActive($lastActive);
        }

        $this->setUtmTags($lead, $utmTags);
    }

    /**
     * Removes a UtmTag set from a Lead.
     *
     * @param Lead $lead
     * @param int  $utmId
     */
    public function removeUtmTags(Lead $lead, $utmId)
    {
        /** @var UtmTag $utmTag */
        foreach ($lead->getUtmTags() as $utmTag) {
            if ($utmTag->getId() === $utmId) {
                $lead->removeUtmTagEntry($utmTag);
                $this->saveEntity($lead);

                return true;
            }
        }

        return false;
    }

    /**
     * Modify tags with support to remove via a prefixed minus sign.
     *
     * @param Lead $lead
     * @param      $tags
     * @param      $removeTags
     * @param      $persist
     * @param bool True if tags modified
     *
     * @return bool
     */
    public function modifyTags(Lead $lead, $tags, array $removeTags = null, $persist = true)
    {
        $tagsModified = false;
        $leadTags     = $lead->getTags();

        if (!$leadTags->isEmpty()) {
            $this->logger->debug('CONTACT: Contact currently has tags '.implode(', ', $leadTags->getKeys()));
        } else {
            $this->logger->debug('CONTACT: Contact currently does not have any tags');
        }

        if (!is_array($tags)) {
            $tags = explode(',', $tags);
        }

        $this->logger->debug('CONTACT: Adding '.implode(', ', $tags).' to contact ID# '.$lead->getId());

        array_walk($tags, create_function('&$val', '$val = trim($val); \Mautic\CoreBundle\Helper\InputHelper::clean($val);'));

        // See which tags already exist
        $foundTags = $this->getTagRepository()->getTagsByName($tags);
        foreach ($tags as $tag) {
            if (strpos($tag, '-') === 0) {
                // Tag to be removed
                $tag = substr($tag, 1);

                if (array_key_exists($tag, $foundTags) && $leadTags->contains($foundTags[$tag])) {
                    $tagsModified = true;
                    $lead->removeTag($foundTags[$tag]);

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            } else {
                $tagToBeAdded = null;

                if (!array_key_exists($tag, $foundTags)) {
                    $tagToBeAdded = new Tag();
                    $tagToBeAdded->setTag($tag);
                } elseif (!$leadTags->contains($foundTags[$tag])) {
                    $tagToBeAdded = $foundTags[$tag];
                }

                if ($tagToBeAdded) {
                    $lead->addTag($tagToBeAdded);
                    $tagsModified = true;
                    $this->logger->debug('CONTACT: Added '.$tag);
                }
            }
        }

        if (!empty($removeTags)) {
            $this->logger->debug('CONTACT: Removing '.implode(', ', $removeTags).' for contact ID# '.$lead->getId());

            array_walk($removeTags, create_function('&$val', '$val = trim($val); \Mautic\CoreBundle\Helper\InputHelper::clean($val);'));

            // See which tags really exist
            $foundRemoveTags = $this->getTagRepository()->getTagsByName($removeTags);

            foreach ($removeTags as $tag) {
                // Tag to be removed
                if (array_key_exists($tag, $foundRemoveTags) && $leadTags->contains($foundRemoveTags[$tag])) {
                    $lead->removeTag($foundRemoveTags[$tag]);
                    $tagsModified = true;

                    $this->logger->debug('CONTACT: Removed '.$tag);
                }
            }
        }

        if ($persist) {
            $this->saveEntity($lead);
        }

        return $tagsModified;
    }

    /**
     * Modify companies for lead.
     *
     * @param Lead $lead
     * @param $companies
     */
    public function modifyCompanies(Lead $lead, $companies)
    {
        // See which companies belong to the lead already
        $leadCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        foreach ($leadCompanies as $key => $leadCompany) {
            if (array_search($leadCompany['company_id'], $companies) === false) {
                $this->companyModel->removeLeadFromCompany([$leadCompany['company_id']], $lead, true);
            }
        }

        if (count($companies)) {
            $this->companyModel->addLeadToCompany($companies, $lead, true);
        } else {
            // update the lead's company name to nothing
            $lead->addUpdatedField('company', '');
            $this->getRepository()->saveEntity($lead);
        }
    }

    /**
     * Get array of available lead tags.
     */
    public function getTagList()
    {
        return $this->getTagRepository()->getSimpleList(null, [], 'tag', 'id');
    }

    /**
     * Get bar chart data of contacts.
     *
     * @param char      $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getLeadsLineChartData($unit, $dateFrom, $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $flag        = null;
        $topLists    = null;
        $allLeadsT   = $this->translator->trans('mautic.lead.all.leads');
        $identifiedT = $this->translator->trans('mautic.lead.identified');
        $anonymousT  = $this->translator->trans('mautic.lead.lead.anonymous');

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $chart                              = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query                              = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $anonymousFilter                    = $filter;
        $anonymousFilter['date_identified'] = [
            'expression' => 'isNull',
        ];
        $identifiedFilter                    = $filter;
        $identifiedFilter['date_identified'] = [
            'expression' => 'isNotNull',
        ];

        if ($flag == 'top') {
            $topLists = $this->leadListModel->getTopLists(6, $dateFrom, $dateTo);
            if ($topLists) {
                foreach ($topLists as $list) {
                    $filter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $all = $query->fetchTimeData('leads', 'date_added', $filter);
                    $chart->setDataset($list['name'].': '.$allLeadsT, $all);
                }
            }
        } elseif ($flag == 'topIdentifiedVsAnonymous') {
            $topLists = $this->leadListModel->getTopLists(3, $dateFrom, $dateTo);
            if ($topLists) {
                foreach ($topLists as $list) {
                    $anonymousFilter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $identifiedFilter['leadlist_id'] = [
                        'value'            => $list['id'],
                        'list_column_name' => 't.id',
                    ];
                    $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
                    $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
                    $chart->setDataset($list['name'].': '.$identifiedT, $identified);
                    $chart->setDataset($list['name'].': '.$anonymousT, $anonymous);
                }
            }
        } elseif ($flag == 'identified') {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $chart->setDataset($identifiedT, $identified);
        } elseif ($flag == 'anonymous') {
            $anonymous = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($anonymousT, $anonymous);
        } elseif ($flag == 'identifiedVsAnonymous') {
            $identified = $query->fetchTimeData('leads', 'date_added', $identifiedFilter);
            $anonymous  = $query->fetchTimeData('leads', 'date_added', $anonymousFilter);
            $chart->setDataset($identifiedT, $identified);
            $chart->setDataset($anonymousT, $anonymous);
        } else {
            $all = $query->fetchTimeData('leads', 'date_added', $filter);
            $chart->setDataset($allLeadsT, $all);
        }

        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getAnonymousVsIdentifiedPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $identified = $query->count('leads', 'date_identified', 'date_added', $filters);
        $all        = $query->count('leads', 'id', 'date_added', $filters);
        $chart->setDataset($this->translator->trans('mautic.lead.identified'), $identified);
        $chart->setDataset($this->translator->trans('mautic.lead.lead.anonymous'), ($all - $identified));

        return $chart->render();
    }

    /**
     * Get leads count per country name.
     * Can't use entity, because country is a custom field.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getLeadMapData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
    {
        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) as quantity, t.country')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->groupBy('t.country')
            ->where($q->expr()->isNotNull('t.country'));

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        $countries = array_flip(Intl::getRegionBundle()->getCountryNames('en'));
        $mapData   = [];

        // Convert country names to 2-char code
        if ($results) {
            foreach ($results as $leadCountry) {
                if (isset($countries[$leadCountry['country']])) {
                    $mapData[$countries[$leadCountry['country']]] = $leadCountry['quantity'];
                }
            }
        }

        return $mapData;
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopOwners($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.owner_id, u.first_name, u.last_name')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = t.owner_id')
            ->where($q->expr()->isNotNull('t.owner_id'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.owner_id, u.first_name, u.last_name')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of top (by leads owned) users.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $filters
     *
     * @return array
     */
    public function getTopCreators($limit = 10, $dateFrom = null, $dateTo = null, $filters = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.id) AS leads, t.created_by, t.created_by_user')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->where($q->expr()->isNotNull('t.created_by'))
            ->andWhere($q->expr()->isNotNull('t.created_by_user'))
            ->orderBy('leads', 'DESC')
            ->groupBy('t.created_by, t.created_by_user')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of leads in a date range.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param array     $options
     *
     * @return array
     */
    public function getLeadList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        if (!empty($options['canViewOthers'])) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.firstname, t.lastname, t.email, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 't')
            ->setMaxResults($limit);

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        if (empty($options['includeAnonymous'])) {
            $q->andWhere($q->expr()->isNotNull('t.date_identified'));
        }
        $results = $q->execute()->fetchAll();

        if ($results) {
            foreach ($results as &$result) {
                if ($result['firstname'] || $result['lastname']) {
                    $result['name'] = trim($result['firstname'].' '.$result['lastname']);
                } elseif ($result['email']) {
                    $result['name'] = $result['email'];
                } else {
                    $result['name'] = 'anonymous';
                }
                unset($result['firstname']);
                unset($result['lastname']);
                unset($result['email']);
            }
        }

        return $results;
    }

    /**
     * Get timeline/engagement data.
     *
     * @param Lead       $lead
     * @param null       $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     *
     * @return array
     */
    public function getEngagements(Lead $lead, $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $event = $this->dispatcher->dispatch(
            LeadEvents::TIMELINE_ON_GENERATE,
            new LeadTimelineEvent($lead, $filters, $orderBy, $page, $limit)
        );

        return [
            'events'   => $event->getEvents(),
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $event->getEventTypes(),
            'total'    => $event->getEventCounter()['total'],
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => $event->getMaxPage(),
        ];
    }

    /**
     * Get engagement counts by time unit.
     *
     * @param Lead            $lead
     * @param \DateTime|null  $dateFrom
     * @param \DateTime|null  $dateTo
     * @param string          $unit
     * @param ChartQuery|null $chartQuery
     *
     * @return array
     */
    public function getEngagementCount(Lead $lead, \DateTime $dateFrom = null, \DateTime $dateTo = null, $unit = 'm', ChartQuery $chartQuery = null)
    {
        $event = new LeadTimelineEvent($lead);
        $event->setCountOnly($dateFrom, $dateTo, $unit, $chartQuery);

        $this->dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $event);

        return $event->getEventCounter();
    }
    /**
     * @param Lead $lead
     * @param      $company
     *
     * @return bool
     */
    public function addToCompany(Lead $lead, $company)
    {
        //check if lead is in company already
        if (!$company instanceof Company) {
            $company = $this->companyModel->getEntity($company);
        }

        // company does not exist anymore
        if ($company === null) {
            return false;
        }

        $companyLead = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId(), $company->getId());

        if (empty($companyLead)) {
            $this->companyModel->addLeadToCompany($company, $lead);

            return true;
        }

        return false;
    }

    /**
     * Get contact channels.
     *
     * @param Lead $lead
     *
     * @return array
     */
    public function getContactChannels(Lead $lead)
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if ($this->isContactable($lead, $channel) === DoNotContact::IS_CONTACTABLE) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Get contact channels.
     *
     * @param Lead $lead
     *
     * @return array
     */
    public function getDoNotContactChannels(Lead $lead)
    {
        $allChannels = $this->getPreferenceChannels();

        $channels = [];
        foreach ($allChannels as $channel) {
            if ($this->isContactable($lead, $channel) !== DoNotContact::IS_CONTACTABLE) {
                $channels[$channel] = $channel;
            }
        }

        return $channels;
    }

    /**
     * @deprecatd 2.4; to be removed in 3.0
     * use mautic.channel.helper.channel_list service (Mautic\ChannelBundle\Helper\ChannelListHelper) to obtain the desired channels
     *
     * Get contact channels.
     *
     * @return array
     */
    public function getAllChannels()
    {
        return $this->channelListHelper->getChannelList();
    }

    /**
     * @return array
     */
    public function getPreferenceChannels()
    {
        return $this->channelListHelper->getFeatureChannels(self::CHANNEL_FEATURE, true);
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function getPreferredChannel(Lead $lead)
    {
        $preferredChannel = $this->getFrequencyRuleRepository()->getPreferredChannel($lead->getId());
        if (!empty($preferredChannel)) {
            return $preferredChannel[0];
        }

        return [];
    }

    /**
     * @param $companyId
     * @param $leadId
     *
     * @return array
     */
    public function setPrimaryCompany($companyId, $leadId)
    {
        $companyArray      = [];
        $oldPrimaryCompany = $newPrimaryCompany = false;

        $lead = $this->getEntity($leadId);

        $companyLeads = $this->companyModel->getCompanyLeadRepository()->getEntitiesByLead($lead);

        foreach ($companyLeads as $companyLead) {
            $company     = $companyLead->getCompany();
            $companyLead = $this->companyModel->getCompanyLeadRepository()->findOneBy(
                [
                    'lead'    => $lead,
                    'company' => $company,
                ]
            );
            if ($companyLead) {
                if ($companyLead->getPrimary()) {
                    $oldPrimaryCompany = $companyLead->getCompany()->getId();
                }

                if ($company->getId() == $companyId and !$companyLead->getPrimary()) {
                    $companyLead->setPrimary(true);
                    $newPrimaryCompany = $companyId;
                    $lead->addUpdatedField('company', $company->getName());
                } else {
                    $companyLead->setPrimary(false);
                }
                $companyArray[] = $companyLead;
            }
        }

        if (!$newPrimaryCompany) {
            $latestCompany = $this->companyModel->getCompanyLeadRepository()->getLatestCompanyForLead($leadId);
            if (!empty($latestCompany)) {
                $lead->addUpdatedField('company', $latestCompany['companyname']);
            }
        }

        if (!empty($companyArray)) {
            $this->em->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            $this->companyModel->getCompanyLeadRepository()->saveEntities($companyArray);
        }

        return ['oldPrimary' => $oldPrimaryCompany, 'newPrimary' => $companyId];
    }

    /**
     * @param Lead $lead
     * @param $score
     *
     * @return bool
     */
    public function scoreContactsCompany(Lead $lead, $score)
    {
        $success          = false;
        $entities         = [];
        $contactCompanies = $this->companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        if (!empty($contactCompanies)) {
            foreach ($contactCompanies as $contactCompany) {
                $company  = $this->companyModel->getEntity($contactCompany['company_id']);
                $oldScore = $company->getScore();
                $newScore = $score + $oldScore;
                $company->setScore($newScore);
                $entities[] = $company;
                $success    = true;
            }
        }

        if (!empty($entities)) {
            $this->companyModel->getRepository()->saveEntities($entities);
        }

        return $success;
    }

    /**
     * @param IpAddress $ip
     * @param bool      $persist
     *
     * @return Lead
     */
    protected function createNewContact(IpAddress $ip, $persist = true)
    {
        //let's create a lead
        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setNewlyCreated(true);

        if ($persist) {
            // Set to prevent loops
            $this->currentLead = $lead;
            $this->saveEntity($lead, false);

            $fields = $this->getLeadDetails($lead);
            $lead->setFields($fields);
        }

        if ($leadId = $lead->getId()) {
            $this->logger->addDebug("LEAD: New lead created with ID# $leadId.");
        }

        return $lead;
    }
}
