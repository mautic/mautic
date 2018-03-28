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

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadChangeCompanyEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class CompanyModel.
 */
class CompanyModel extends CommonFormModel implements AjaxLookupModelInterface
{
    use DefaultValueTrait, RequestTrait;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var array
     */
    protected $companyFields;

    /**
     * @var EmailValidator
     */
    protected $emailValidator;

    /**
     * CompanyModel constructor.
     *
     * @param FieldModel     $leadFieldModel
     * @param Session        $session
     * @param EmailValidator $validator
     */
    public function __construct(FieldModel $leadFieldModel, Session $session, EmailValidator $validator)
    {
        $this->leadFieldModel = $leadFieldModel;
        $this->session        = $session;
        $this->emailValidator = $validator;
    }

    /**
     * @param Company $entity
     * @param bool    $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        // Update leads primary company name
        $this->setEntityDefaultValues($entity, 'company');
        $this->getCompanyLeadRepository()->updateLeadsPrimaryCompanyName($entity);

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Save an array of entities.
     *
     * @param array $entities
     * @param bool  $unlock
     *
     * @return array
     */
    public function saveEntities($entities, $unlock = true)
    {
        // Update leads primary company name
        foreach ($entities as $k => $entity) {
            $this->setEntityDefaultValues($entity, 'company');
            $this->getCompanyLeadRepository()->updateLeadsPrimaryCompanyName($entity);
        }
        parent::saveEntities($entities, $unlock);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\CompanyRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Company');
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\CompanyLeadRepository
     */
    public function getCompanyLeadRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:CompanyLead');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'company:companies';
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
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Company) {
            throw new MethodNotAllowedHttpException(['Company']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('company', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return Company|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Company();
        }

        return parent::getEntity($id);
    }

    /**
     * @return mixed
     */
    public function getUserCompanies()
    {
        $user = (!$this->security->isGranted('lead:leads:viewother')) ?
            $this->userHelper->getUser() : false;
        $companies = $this->em->getRepository('MauticLeadBundle:Company')->getCompanies($user);

        return $companies;
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
                if ($field->getObject() === 'company') {
                    $group                          = $field->getGroup();
                    $array[$group][$alias]['id']    = $field->getId();
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field->getLabel();
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field->getType();
                }
            } else {
                $alias   = $field['alias'];
                $field[] = $alias;
                if ($field['object'] === 'company') {
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
     * Populates custom field values for updating the company.
     *
     * @param Company    $company
     * @param array      $data
     * @param bool|false $overwriteWithBlank
     */
    public function setFieldValues(Company $company, array $data, $overwriteWithBlank = false)
    {
        //save the field values
        $fieldValues = $company->getFields();

        if (empty($fieldValues)) {
            // Lead is new or they haven't been populated so let's build the fields now
            static $fields;
            if (empty($fields)) {
                $fields = $this->leadFieldModel->getEntities(
                    [
                        'filter'         => ['object' => 'company'],
                        'hydration_mode' => 'HYDRATE_ARRAY',
                    ]
                );
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

                    if (is_array($newValue)) {
                        $newValue = implode('|', $newValue);
                    }

                    if ($curValue !== $newValue && (strlen($newValue) > 0 || (strlen($newValue) === 0 && $overwriteWithBlank))) {
                        $field['value'] = $newValue;
                        $company->addUpdatedField($alias, $newValue, $curValue);
                    }
                }
            }
        }
        $company->setFields($fieldValues);
    }

    /** Add lead to company
     * @param array|Company $companies
     * @param array|Lead    $lead
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function addLeadToCompany($companies, $lead)
    {
        // Primary company name to be peristed to the lead's contact company field
        $companyName        = '';
        $companyLeadAdd     = [];
        $searchForCompanies = [];

        $dateManipulated = new \DateTime();

        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }

        if ($companies instanceof Company) {
            $companyLeadAdd[$companies->getId()] = $companies;
            $companies                           = [$companies->getId()];
        } elseif (!is_array($companies)) {
            $companies = [$companies];
        }

        //make sure they are ints
        foreach ($companies as $k => &$l) {
            $l = (int) $l;

            if (!isset($companyLeadAdd[$l])) {
                $searchForCompanies[] = $l;
            }
        }

        if (!empty($searchForCompanies)) {
            $companyEntities = $this->getEntities([
                'filter' => [
                    'force' => [
                        [
                            'column' => 'comp.id',
                            'expr'   => 'in',
                            'value'  => $searchForCompanies,
                        ],
                    ],
                ],
            ]);

            foreach ($companyEntities as $company) {
                $companyLeadAdd[$company->getId()] = $company;
            }
        }

        unset($companyEntities, $searchForCompanies);

        $persistCompany = [];
        $dispatchEvents = [];
        $contactAdded   = false;
        foreach ($companies as $companyId) {
            if (!isset($companyLeadAdd[$companyId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            $companyLead = $this->getCompanyLeadRepository()->findOneBy(
                [
                    'lead'    => $lead,
                    'company' => $companyLeadAdd[$companyId],
                ]
            );

            if ($companyLead != null) {
                // @deprecated support to be removed in 3.0
                if ($companyLead->wasManuallyRemoved()) {
                    $companyLead->setManuallyRemoved(false);
                    $companyLead->setManuallyAdded(false);
                    $contactAdded     = true;
                    $persistCompany[] = $companyLead;
                    $dispatchEvents[] = $companyId;
                    $companyName      = $companyLeadAdd[$companyId]->getName();
                } else {
                    // Detach from Doctrine
                    $this->em->detach($companyLead);

                    continue;
                }
            } else {
                $companyLead = new CompanyLead();
                $companyLead->setCompany($companyLeadAdd[$companyId]);
                $companyLead->setLead($lead);
                $companyLead->setDateAdded($dateManipulated);
                $contactAdded     = true;
                $persistCompany[] = $companyLead;
                $dispatchEvents[] = $companyId;
                $companyName      = $companyLeadAdd[$companyId]->getName();
            }
        }

        if (!empty($persistCompany)) {
            $this->getCompanyLeadRepository()->saveEntities($persistCompany);
        }

        if (!empty($companyName)) {
            $currentCompanyName = $lead->getCompany();
            if ($currentCompanyName !== $companyName) {
                $lead->addUpdatedField('company', $companyName)
                    ->setDateModified(new \DateTime());
                $this->em->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        }

        if (!empty($dispatchEvents) && ($this->dispatcher->hasListeners(LeadEvents::LEAD_COMPANY_CHANGE))) {
            foreach ($dispatchEvents as $companyId) {
                $event = new LeadChangeCompanyEvent($lead, $companyLeadAdd[$companyId]);
                $this->dispatcher->dispatch(LeadEvents::LEAD_COMPANY_CHANGE, $event);

                unset($event);
            }
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->em->clear(CompanyLead::class);

        return $contactAdded;
    }

    /**
     * Remove a lead from company.
     *
     * @param   $companies
     * @param   $lead
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeLeadFromCompany($companies, $lead)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }

        $companyLeadRemove = [];
        if (!$companies instanceof Company) {
            //make sure they are ints
            $searchForCompanies = [];
            foreach ($companies as $k => &$l) {
                $l = (int) $l;
                if (!isset($companyLeadRemove[$l])) {
                    $searchForCompanies[] = $l;
                }
            }
            if (!empty($searchForCompanies)) {
                $companyEntities = $this->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => 'comp.id',
                                    'expr'   => 'in',
                                    'value'  => $searchForCompanies,
                                ],
                            ],
                        ],
                    ]
                );

                foreach ($companyEntities as $company) {
                    $companyLeadRemove[$company->getId()] = $company;
                }
            }

            unset($companyEntities, $searchForCompanies);
        } else {
            $companyLeadRemove[$companies->getId()] = $companies;

            $companies = [$companies->getId()];
        }

        if (!is_array($companies)) {
            $companies = [$companies];
        }

        $deleteCompany  = [];
        $dispatchEvents = [];

        foreach ($companies as $companyId) {
            if (!isset($companyLeadRemove[$companyId])) {
                continue;
            }

            $companyLead = $this->getCompanyLeadRepository()->findOneBy(
                [
                    'lead'    => $lead,
                    'company' => $companyLeadRemove[$companyId],
                ]
            );

            if ($companyLead == null) {
                // Lead is not part of this list
                continue;
            }

            //lead was manually added and now manually removed or was not manually added and now being removed
            $deleteCompanyLead[] = $companyLead;
            $dispatchEvents[]    = $companyId;

            unset($companyLead);
        }

        if (!empty($deleteCompanyLead)) {
            $this->getCompanyLeadRepository()->deleteEntities($deleteCompanyLead);
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->em->clear('Mautic\CompanyBundle\Entity\CompanyLead');

        if (!empty($dispatchEvents) && ($this->dispatcher->hasListeners(LeadEvents::LEAD_COMPANY_CHANGE))) {
            foreach ($dispatchEvents as $listId) {
                $event = new LeadChangeCompanyEvent($lead, $companyLeadRemove[$listId], false);
                $this->dispatcher->dispatch(LeadEvents::LEAD_COMPANY_CHANGE, $event);

                unset($event);
            }
        }

        unset($lead, $deleteCompany, $persistCompany, $companies);
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
            case 'companyfield':
            case 'lead.company':
                $expr = null;

                if ('lead.company' === $type) {
                    $column    = 'companyname';
                    $filterVal = $filter;
                } else {
                    if (is_array($filter)) {
                        $column    = $filter[0];
                        $filterVal = $filter[1];
                    } else {
                        $column = $filter;
                    }
                }

                $expr      = new ExpressionBuilder($this->em->getConnection());
                $composite = $expr->andX();
                $composite->add(
                    $expr->like("comp.$column", ':filterVar')
                );

                // Validate owner permissions
                if (!$this->security->isGranted('lead:leads:viewother')) {
                    $composite->add(
                        $expr->orX(
                            $expr->andX(
                                $expr->isNull('comp.owner_id'),
                                $expr->eq('comp.created_by', (int) $this->userHelper->getUser()->getId())
                            ),
                            $expr->eq('comp.owner_id', (int) $this->userHelper->getUser()->getId())
                        )
                    );
                }

                $results = $this->getRepository()->getAjaxSimpleList($composite, ['filterVar' => $filterVal.'%'], $column);

                break;
        }

        return $results;
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
        if (!$entity instanceof Company) {
            throw new MethodNotAllowedHttpException(['Email']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::COMPANY_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::COMPANY_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::COMPANY_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::COMPANY_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new CompanyEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Company Merge function, will merge $mainCompany with $secCompany -  empty records from main company will be
     * filled with secondary then secondary will be deleted.
     *
     * @param $mainCompany
     * @param $secCompany
     *
     * @return mixed
     */
    public function companyMerge($mainCompany, $secCompany)
    {
        $this->logger->debug('COMPANY: Merging companies');

        $mainCompanyId = $mainCompany->getId();
        $secCompanyId  = $secCompany->getId();

        //if they are the same lead, then just return one
        if ($mainCompanyId === $secCompanyId) {
            return $mainCompany;
        }
        //merge fields
        $mergeSecFields    = $secCompany->getFields();
        $mainCompanyFields = $mainCompany->getFields();
        foreach ($mergeSecFields as $group => $groupFields) {
            foreach ($groupFields as $alias => $details) {
                //fill in empty main company fields with secondary company fields
                if (empty($mainCompanyFields[$group][$alias]['value']) && !empty($details['value'])) {
                    $mainCompany->addUpdatedField($alias, $details['value']);
                    $this->logger->debug('Company: Updated '.$alias.' = '.$details['value']);
                }
            }
        }

        //merge owner
        $mainCompanyOwner = $mainCompany->getOwner();
        $secCompanyOwner  = $secCompany->getOwner();

        if ($mainCompanyOwner === null && $secCompanyOwner !== null) {
            $mainCompany->setOwner($secCompanyOwner);
        }

        //move all leads from secondary company to main company
        $companyLeadRepo = $this->getCompanyLeadRepository();
        $secCompanyLeads = $companyLeadRepo->getCompanyLeads($secCompanyId);

        foreach ($secCompanyLeads as $lead) {
            $this->addLeadToCompany($mainCompany->getId(), $lead['lead_id']);
        }
        //save the updated company
        $this->saveEntity($mainCompany, false);

        //delete the old company
        $this->deleteEntity($secCompany);

        //return the merged company
        return $mainCompany;
    }

    /**
     * @return array
     */
    public function fetchCompanyFields()
    {
        if (empty($this->companyFields)) {
            $this->companyFields = $this->leadFieldModel->getEntities(
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
                                'value'  => 'company',
                            ],
                        ],
                    ],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                ]
            );
        }

        return $this->companyFields;
    }

    /**
     * @param $mappedFields
     * @param $data
     *
     * @return array
     */
    public function extractCompanyDataFromImport(array &$mappedFields, array &$data)
    {
        $companyData    = [];
        $companyFields  = [];
        $internalFields = $this->fetchCompanyFields();

        foreach ($mappedFields as $mauticField => $importField) {
            foreach ($internalFields as $entityField) {
                if ($entityField['alias'] === $mauticField) {
                    $companyData[$importField]   = $data[$importField];
                    $companyFields[$mauticField] = $importField;
                    unset($data[$importField]);
                    unset($mappedFields[$mauticField]);
                    break;
                }
            }
        }

        return [$companyFields, $companyData];
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
    public function import($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null)
    {
        $fields = array_flip($fields);

        // Let's check for an existing company by name
        $hasName  = (!empty($fields['companyname']) && !empty($data[$fields['companyname']]));
        $hasEmail = (!empty($fields['companyemail']) && !empty($data[$fields['companyemail']]));

        if ($hasEmail) {
            $this->emailValidator->validate($data[$fields['companyemail']], false);
        }

        if ($hasName) {
            $companyName    = isset($fields['companyname']) ? $data[$fields['companyname']] : null;
            $companyCity    = isset($fields['companycity']) ? $data[$fields['companycity']] : null;
            $companyCountry = isset($fields['companycountry']) ? $data[$fields['companycountry']] : null;
            $companyState   = isset($fields['companystate']) ? $data[$fields['companystate']] : null;

            $found   = $companyName ? $this->getRepository()->identifyCompany($companyName, $companyCity, $companyCountry, $companyState) : false;
            $company = ($found) ? $this->em->getReference('MauticLeadBundle:Company', $found['id']) : new Company();
            $merged  = $found;
        } else {
            return null;
        }

        if (!empty($fields['dateAdded']) && !empty($data[$fields['dateAdded']])) {
            $dateAdded = new DateTimeHelper($data[$fields['dateAdded']]);
            $company->setDateAdded($dateAdded->getUtcDateTime());
        }
        unset($fields['dateAdded']);

        if (!empty($fields['dateModified']) && !empty($data[$fields['dateModified']])) {
            $dateModified = new DateTimeHelper($data[$fields['dateModified']]);
            $company->setDateModified($dateModified->getUtcDateTime());
        }
        unset($fields['dateModified']);

        if (!empty($fields['createdByUser']) && !empty($data[$fields['createdByUser']])) {
            $userRepo      = $this->em->getRepository('MauticUserBundle:User');
            $createdByUser = $userRepo->findByIdentifier($data[$fields['createdByUser']]);
            if ($createdByUser !== null) {
                $company->setCreatedBy($createdByUser);
            }
        }
        unset($fields['createdByUser']);

        if (!empty($fields['modifiedByUser']) && !empty($data[$fields['modifiedByUser']])) {
            $userRepo       = $this->em->getRepository('MauticUserBundle:User');
            $modifiedByUser = $userRepo->findByIdentifier($data[$fields['modifiedByUser']]);
            if ($modifiedByUser !== null) {
                $company->setModifiedBy($modifiedByUser);
            }
        }
        unset($fields['modifiedByUser']);

        if ($owner !== null) {
            $company->setOwner($this->em->getReference('MauticUserBundle:User', $owner));
        }

        // Set profile data using the form so that values are validated
        $fieldData = [];
        foreach ($fields as $entityField => $importField) {
            // Prevent overwriting existing data with empty data
            if (array_key_exists($importField, $data) && !is_null($data[$importField]) && $data[$importField] != '') {
                $fieldData[$entityField] = $data[$importField];
            }
        }

        $fieldErrors = [];

        foreach ($this->fetchCompanyFields() as $entityField) {
            if (isset($fieldData[$entityField['alias']])) {
                $fieldData[$entityField['alias']] = InputHelper::_($fieldData[$entityField['alias']], 'string');

                if ('NULL' === $fieldData[$entityField['alias']]) {
                    $fieldData[$entityField['alias']] = null;

                    continue;
                }

                try {
                    $this->cleanFields($fieldData, $entityField);
                } catch (\Exception $exception) {
                    $fieldErrors[] = $entityField['alias'].': '.$exception->getMessage();
                }

                // Skip if the value is in the CSV row
                continue;
            } elseif ($entityField['defaultValue']) {
                // Fill in the default value if any
                $fieldData[$entityField['alias']] = ('multiselect' === $entityField['type']) ? [$entityField['defaultValue']] : $entityField['defaultValue'];
            }
        }

        if ($fieldErrors) {
            $fieldErrors = implode("\n", $fieldErrors);

            throw new \Exception($fieldErrors);
        }

        // All clear
        foreach ($fieldData as $field => $value) {
            $company->addUpdatedField($field, $value);
        }

        if ($persist) {
            $this->saveEntity($company);
        }

        return $merged;
    }
}
