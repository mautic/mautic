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
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
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
    use DefaultValueTrait;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * CompanyModel constructor.
     *
     * @param FieldModel $leadFieldModel
     * @param Session    $session
     */
    public function __construct(FieldModel $leadFieldModel, Session $session)
    {
        $this->leadFieldModel = $leadFieldModel;
        $this->session        = $session;
    }

    /**
     * @param Company $entity
     * @param bool    $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        $this->setEntityDefaultValues($entity, 'company');

        parent::saveEntity($entity, $unlock);
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
     *
     * @return array
     */
    public function setFieldValues(Company &$company, array $data, $overwriteWithBlank = false)
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
     * @param bool          $manuallyAdded
     * @param bool          $batchProcess
     * @param int           $searchCompanyLead 0 = reference, 1 = yes, -1 = known to not exist
     * @param \DateTime     $dateManipulated
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function addLeadToCompany($companies, $lead, $manuallyAdded = false, $searchCompanyLead = 1, $dateManipulated = null)
    {
        // Primary company name to be peristed to the lead's contact company field
        $companyName        = '';
        $companyLeadAdd     = [];
        $searchForCompanies = [];

        if ($dateManipulated == null) {
            $dateManipulated = new \DateTime();
        }

        if ($lead instanceof Lead) {
            $leadId = $lead->getId();
        } else {
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

        foreach ($companies as $companyId) {
            if (!isset($companyLeadAdd[$companyId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            if ($searchCompanyLead == -1) {
                $companyLead = null;
            } elseif ($searchCompanyLead) {
                $companyLead = $this->getCompanyLeadRepository()->findOneBy(
                    [
                        'lead'    => $lead,
                        'company' => $companyLeadAdd[$companyId],
                    ]
                );
            } else {
                $companyLead = $this->em->getReference('MauticLeadBundle:CompanyLead',
                    [
                        'lead'    => $leadId,
                        'company' => $companyId,
                    ]
                );
            }

            if ($companyLead != null) {
                if ($manuallyAdded && $companyLead->wasManuallyRemoved()) {
                    $companyLead->setManuallyRemoved(false);
                    $companyLead->setManuallyAdded($manuallyAdded);

                    $persistLists[]   = $companyLead;
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
                $companyLead->setManuallyAdded($manuallyAdded);
                $companyLead->setDateAdded($dateManipulated);

                $persistCompany[] = $companyLead;
                $dispatchEvents[] = $companyId;
                $companyName      = $companyLeadAdd[$companyId]->getName();
            }
        }

        if (!empty($persistCompany)) {
            $this->getCompanyLeadRepository()->saveEntities($persistCompany);
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->em->clear('Mautic\CompanyBundle\Entity\CompanyLead');

        if (!empty($companyName)) {
            $currentCompanyName = $lead->getCompany();
            if ($currentCompanyName !== $companyName) {
                $lead->addUpdatedField('company', $companyName);
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

        unset($lead, $persistCompany, $companies);
    }

    /**
     * Remove a lead from company.
     *
     * @param      $companies
     * @param      $lead
     * @param bool $manuallyRemoved
     * @param bool $batchProcess
     * @param bool $skipFindOne
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeLeadFromCompany($companies, $lead, $manuallyRemoved = false, $batchProcess = false, $skipFindOne = false)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        } else {
            $leadId = $lead->getId();
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
        $persistCompany = [];
        $deleteCompany  = [];
        $dispatchEvents = [];

        foreach ($companies as $companyId) {
            if (!isset($companyLeadRemove[$companyId])) {
                continue;
            }

            $companyLead = (!$skipFindOne) ?
                $this->getCompanyLeadRepository()->findOneBy([
                    'lead'    => $lead,
                    'company' => $companyLeadRemove[$companyId],
                ]) :
                $this->em->getReference('MauticCompanyBundle:CompanyLead', [
                    'lead'    => $leadId,
                    'company' => $companyId,
                ]);

            if ($companyLead == null) {

                // Lead is not part of this list
                continue;
            }

            if (($manuallyRemoved && $companyLead->wasManuallyAdded()) || (!$manuallyRemoved && !$companyLead->wasManuallyAdded())) {
                //lead was manually added and now manually removed or was not manually added and now being removed
                $deleteCompanyLead[] = $companyLead;
                $dispatchEvents[]    = $companyId;
            } elseif ($manuallyRemoved && !$companyLead->wasManuallyAdded()) {
                $companyLead->setManuallyRemoved(true);

                $persistCompany[] = $companyLead;
                $dispatchEvents[] = $companyId;
            }

            unset($companyLead);
        }

        if (!empty($persistCompany)) {
            $this->getCompanyLeadRepository()->saveEntities($persistCompany);
        }
        if (!empty($deleteCompanyLead)) {
            $this->getCompanyLeadRepository()->deleteEntities($deleteCompanyLead);
        }

        // Clear CompanyLead entities from Doctrine memory
        $this->em->clear('Mautic\CompanyBundle\Entity\CompanyLead');

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        } elseif (!empty($dispatchEvents) && ($this->dispatcher->hasListeners(LeadEvents::LEAD_COMPANY_CHANGE))) {
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
}
