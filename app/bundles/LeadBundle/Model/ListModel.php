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
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Event\LeadListEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ListModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ListModel extends FormModel
{
    /**
     * Used by addLead and removeLead functions
     *
     * @var array
     */
    private $leadChangeLists = array();

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\LeadListRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadList');
    }

    /**
     * Returns the repository for the table that houses the leads associated with a list
     *
     * @return \Mautic\LeadBundle\Entity\ListLeadRepository
     */
    public function getListLeadRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:ListLead');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:list';
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param bool $unlock
     * @return mixed|void
     */
    public function saveEntity($entity, $unlock = true)
    {
        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getName(), false, '-'));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, false, '-'));
        }

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $user      = $this->factory->getUser();
        $existing  = $repo->getLists($user, $testAlias, $entity->getId());
        $count     = count($existing);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $existing  = $repo->getLists($user, $testAlias, $entity->getId());
            $count     = count($existing);
            $aliasTag++;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(array('LeadList'), 'Entity must be of class LeadList()');
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('leadlist', $entity, $params);
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
            return new LeadList();
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
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(array('LeadList'), 'Entity must be of class LeadList()');
        }

        switch ($action) {
            case "pre_save":
                $name = LeadEvents::LIST_PRE_SAVE;
                break;
            case "post_save":
                $name = LeadEvents::LIST_POST_SAVE;
                break;
            case "pre_delete":
                $name = LeadEvents::LIST_PRE_DELETE;
                break;
            case "post_delete":
                $name = LeadEvents::LIST_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadListEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch(LeadEvents::LIST_PRE_SAVE, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getFilterExpressionFunctions()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadList')->getFilterExpressionFunctions();
    }

    /**
     * Get a list of field choices for filters
     *
     * @return array
     */
    public function getChoiceFields()
    {
        //field choices
        $choices = array(
            'date_added' => array(
                'label'       => $this->translator->trans('mautic.core.date.added'),
                'properties'  => array('type' => 'date')
            ),
            'owner_id'     => array(
                'label'      => $this->translator->trans('mautic.lead.list.filter.owner'),
                'properties' => array(
                    'type'     => 'lookup_id',
                    'callback' => 'activateLeadFieldTypeahead'
                )
            ),
            'points'     => array(
                'label'      => $this->translator->trans('mautic.lead.lead.event.points'),
                'properties' => array('type' => 'number')
            )
        );

        //get list of custom fields
        $fields = $this->factory->getModel('lead.field')->getEntities(
            array('filter' => array(
                'isListable'  => true,
                'isPublished' => true
            ))
        );
        foreach ($fields as $field) {
            $type = $field->getType();
            $properties = $field->getProperties();
            $properties['type'] = $type;
            if (in_array($type, array('lookup', 'select', 'boolean'))) {
                $properties['callback'] = 'activateLeadFieldTypeahead';
                if ($type == 'boolean') {
                    //create a lookup list with ID
                    $properties['list'] = $properties['yes'].'|'.$properties['no'] . '||1|0';
                }
            }
            $choices[$field->getAlias()] = array(
                'label'      => $field->getLabel(),
                'properties' => $properties
            );
        }

        $cmp = function ($a, $b) {
            return strcmp($a["label"], $b["label"]);
        };

        uasort($choices, $cmp);
        return $choices;
    }

    /**
     * @param string $alias
     * @param bool $withLeads
     *
     * @return mixed
     */
    public function getUserLists($alias = '', $withLeads = false)
    {
        $user  = (!$this->security->isGranted('lead:lists:viewother')) ?
            $this->factory->getUser() : false;
        $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getLists($user, $alias, '', $withLeads);

        return $lists;
    }

    /**
     * Get a list of global lead lists
     *
     * @param bool $withLeads
     *
     * @return mixed
     */
    public function getGlobalLists($withLeads = false)
    {
        $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getGlobalLists($withLeads);
        return $lists;
    }

    /**
     * Rebuild lead lists
     *
     * @param LeadList $entity
     * @param          $limit
     * @param          $batchsize
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function rebuildListLeads(LeadList $entity, $limit = 1000, $maxLeads = false, OutputInterface $output = null)
    {
        defined('MAUTIC_REBUILDING_LEAD_LISTS') or define('MAUTIC_REBUILDING_LEAD_LISTS', 1);

        $id   = $entity->getId();
        $list = array('id' => $id, 'filters' => $entity->getFilters());

        $batchLimiters = array(
            'dateTime' => $this->factory->getDate()->toUtcString()
        );

        // Get a count of leads to add
        $newLeadsCount = $this->getLeadsByList(
            $list,
            true,
            array(
                'countOnly'     => true,
                'newOnly'       => true,
                'dynamic'       => true,
                'includeManual' => false,
                'batchLimiters' => $batchLimiters
            )
        );

        // Ensure the same list is used each batch
        $batchLimiters['maxId'] = (int) $newLeadsCount[$id]['maxId'];

        // Number of total leads to process
        $leadCount = (int) $newLeadsCount[$id]['count'];

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_added', array('%leads%' => $leadCount, '%batch%' => $limit)));
        }

        // Handle by batches
        $start = $lastRoundPercentage = $leadsProcessed = 0;

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            $maxCount = ($maxLeads) ? $maxLeads : $leadCount;

            if ($output) {
                $progress = new ProgressBar($output, $maxCount);
                $progress->start();
            }

            // Add leads
            while ($start < $leadCount) {
                // Keep CPU down
                sleep(2);

                $newLeadList = $this->getLeadsByList(
                    $list,
                    true,
                    array(
                        'dynamic'       => true,
                        'newOnly'       => true,
                        'includeManual' => false,
                        // No start set because of newOnly thus always at 0
                        'limit'         => $limit,
                        'batchLimiters' => $batchLimiters
                    )
                );

                if (empty($newLeadList[$id])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                foreach ($newLeadList[$id] as $l) {
                    // Keep RAM down
                    usleep(500);

                    $this->addLead($l, $entity, false, true, -1);

                    unset($l);

                    $leadsProcessed++;

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                $start += $limit;

                if ($output && $leadsProcessed < $maxCount) {
                    $progress->setCurrent($leadsProcessed);
                }

                // Dispatch batch event
                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    // Keep RAM down
                    sleep(2);

                    $event = new ListChangeEvent($newLeadList[$id], $entity, true);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_BATCH_CHANGE, $event);

                    unset($event);
                }

                unset($newLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        $fullList = $this->getLeadsByList(
            $list,
            true,
            array(
                'dynamic'       => true,
                'existingOnly'  => true,
                'includeManual' => false,
                'batchLimiters' => $batchLimiters
            )
        );

        // Get a count of leads to be removed
        $removeLeadCount = $this->getLeadsByList(
            $list,
            true,
            array(
                'countOnly'     => true,
                'includeManual' => false,
                'filterOutIds'  => $fullList[$id],
                'batchLimiters' => $batchLimiters
            )
        );

        // Restart batching
        $start     = $lastRoundPercentage = 0;
        $leadCount = $removeLeadCount[$id]['count'];

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_removed', array('%leads%' => $leadCount, '%batch%' => $limit)));
        }

        if ($leadCount) {

            $maxCount = ($maxLeads) ? $maxLeads : $leadCount;

            if ($output) {
                $progress = new ProgressBar($output, $maxCount);
                $progress->start();
            }

            // Remove leads
            while ($start < $leadCount) {
                // Keep CPU down
                sleep(2);

                $removeLeadList = $this->getLeadsByList(
                    $list,
                    true,
                    array(
                        // No start because the items are deleted so always 0
                        'limit'         => $limit,
                        'filterOutIds'  => $fullList[$id],
                        'batchLimiters' => $batchLimiters
                    )
                );

                if (empty($removeLeadList[$id])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                foreach ($removeLeadList[$id] as $l) {
                    // Keep RAM down
                    usleep(500);

                    $this->removeLead($l, $entity, false, true, true);

                    $leadsProcessed++;

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                // Dispatch batch event
                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    // Keep RAM down
                    sleep(2);

                    $event = new ListChangeEvent($removeLeadList[$id], $entity, false);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_BATCH_CHANGE, $event);

                    unset($event);
                }

                $start += $limit;

                if ($output && $leadsProcessed < $maxCount) {
                    $progress->setCurrent($leadsProcessed);
                }


                unset($removeLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        return $leadsProcessed;
    }

    /**
     * Add lead to lists
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyAdded
     * @param bool $batchProcess
     * @param int  $searchListLead 0 = reference, 1 = yes, -1 = known to not exist
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function addLead($lead, $lists, $manuallyAdded = false, $batchProcess = false, $searchListLead = 1)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            //make sure they are ints
            $searchForLists = array();
            foreach ($lists as $k => &$l) {
                $l = (int) $l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities(array(
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
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);
        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = array($lists->getId());
        }

        if (!is_array($lists)) {
            $lists = array($lists);
        }

        $persistLists = array();

        foreach ($lists as $l) {
            if (!isset($this->leadChangeLists[$l])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            if ($searchListLead == -1) {
                $listLead = null;
            } elseif ($searchListLead) {
                $listLead = $this->getListLeadRepository()->findOneBy(
                    array(
                        'lead' => $lead,
                        'list' => $this->leadChangeLists[$l]
                    )
                );
            } else {
                $listLead = $this->em->getReference('MauticLeadBundle:ListLead',
                    array(
                        'lead' => $leadId,
                        'list' => $l
                    )
                );
            }

            if ($listLead != null) {
                if ($manuallyAdded && $listLead->wasManuallyRemoved()) {
                    $listLead->setManuallyRemoved(false);
                    $listLead->setManuallyAdded($manuallyAdded);

                    $persistLists[] = $listLead;
                } else {
                    // Detach from Doctrine
                    $this->em->detach($listLead);

                    continue;
                }
            } else {
                $listLead = new ListLead();
                $listLead->setList($this->leadChangeLists[$l]);
                $listLead->setLead($lead);
                $listLead->setManuallyAdded($manuallyAdded);
                $listLead->setDateAdded(new \DateTime());

                $persistLists[] = $listLead;
            }

            if (!$batchProcess && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$l], true);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);

                unset($event);
            }
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);

            // Detach the entities to save memory
            foreach ($persistLists as $l) {
                $this->em->detach($l);
                unset($l);
            }
        }

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        }

        unset($lead, $persistLists, $lists);
    }

    /**
     * Remove a lead from lists
     *
     * @param      $lead
     * @param      $lists
     * @param bool $manuallyRemoved
     * @param bool $batchProcess
     * @param bool $skipFindOne
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeLead($lead, $lists, $manuallyRemoved = false, $batchProcess = false, $skipFindOne = false)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            //make sure they are ints
            $searchForLists = array();
            foreach ($lists as $k => &$l) {
                $l = (int)$l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities(array(
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
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);

        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = array($lists->getId());
        }

        if (!is_array($lists)) {
            $lists = array($lists);
        }

        $persistLists = $deleteLists = array();
        foreach ($lists as $l) {
            if (!isset($this->leadChangeLists[$l])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            $dispatchEvent = false;

            $listLead = (!$skipFindOne) ?
                $this->getListLeadRepository()->findOneBy(array(
                    'lead' => $lead,
                    'list' => $this->leadChangeLists[$l]
                )) :
                $this->em->getReference('MauticLeadBundle:ListLead', array(
                    'lead' => $leadId,
                    'list' => $l
                ));

            if ($listLead == null) {
                // Lead is not part of this list
                continue;
            }

            if (($manuallyRemoved && $listLead->wasManuallyAdded()) || (!$manuallyRemoved && !$listLead->wasManuallyAdded())) {
                //lead was manually added and now manually removed or was not manually added and now being removed
                $dispatchEvent = true;

                $deleteLists[] = $listLead;
            } elseif ($manuallyRemoved && !$listLead->wasManuallyAdded()) {
                $dispatchEvent = true;

                $listLead->setManuallyRemoved(true);

                $persistLists[] = $listLead;
            }

            unset($listLead);

            if (!$batchProcess && $dispatchEvent && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$l], false);
                $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);

                unset($event);
            }
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);

            // Detach the entities to save memory
            foreach ($persistLists as $l) {
                $this->em->detach($l);
                unset($l);
            }
        }

        if (!empty($deleteLists)) {
            $this->getRepository()->deleteEntities($deleteLists);
        }

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        }

        unset($lead, $deleteLists, $persistLists, $lists);
    }


    /**
     * @param       $lists
     * @param bool  $idOnly
     * @param array $args
     *
     * @return mixed
     */
    public function getLeadsByList($lists, $idOnly = false, $args = array())
    {
        $args['idOnly'] = $idOnly;

        return $this->getRepository()->getLeadsByList($lists, $args);
    }
}
