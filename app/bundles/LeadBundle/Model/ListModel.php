<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ListModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ListModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadList');
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
        $repo  = $this->getRepository();

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getName(), true));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, true));
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

        $this->regenerateListLeads($entity, $isNew, false);

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);
    }

    /**
     *
     *
     * @param LeadList $entity
     * @param          $isNew
     * @param          $persist
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function regenerateListLeads(LeadList $entity, $isNew = false, $persist = true)
    {
        if (!$isNew) {
            $id = $entity->getId();

            $oldLeadList = $this->getLeadsByList(array('id' => $id), true);
            $newLeadList = $this->getLeadsByList(array('id' => $id, 'filters' => $entity->getFilters()), true, true);

            $addLeads    = array_diff($newLeadList[$id], $oldLeadList[$id]);
            $removeLeads = array_diff($oldLeadList[$id], $newLeadList[$id]);
        } else {
            $newLeadList = $this->getLeadsByList(array('id' => 'new', 'filters' => $entity->getFilters()), true);

            $addLeads    = $newLeadList['new'];
            $removeLeads = array();
        }

        foreach ($addLeads as $l) {
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $l);
            if ($entity->addLead($lead, true)) {
                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                    $event = new ListChangeEvent($lead, $entity, true);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
                }
            }
        }

        foreach ($removeLeads as $l) {
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $l);
            if ($entity->removeLead($lead, true)) {
                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
                    $event = new ListChangeEvent($lead, $entity, false);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_LIST_CHANGE, $event);
                }
            }
        }

        if ($persist) {
            $this->getRepository()->saveEntity($entity);
        }
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
            'dateAdded' => array(
                'label'       => $this->translator->trans('mautic.lead.list.filter.dateadded'),
                'properties'  => array('type' => 'date')
            ),
            'owner'     => array(
                'label'      => $this->translator->trans('mautic.lead.list.filter.owner'),
                'properties' => array(
                    'type'     => 'lookup_id',
                    'callback' => 'activateLeadFieldTypeahead'
                )
            ),
            'points'     => array(
                'label'      => $this->translator->trans('mautic.lead.list.filter.points'),
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
     * @param $lead
     * @param $lists
     */
    public function addLead($lead, $lists)
    {
        $this->factory->getModel('lead')->addToLists($lead, $lists);
    }

    /**
     * @param $lead
     * @param $lists
     */
    public function removeLead($lead, $lists)
    {
        $this->factory->getModel('lead')->removeFromLists($lead, $lists);
    }

    /**
     * @param      $lists
     * @param bool $idOnly
     * @param bool $dynamic
     *
     * @return mixed
     */
    public function getLeadsByList($lists, $idOnly = false, $dynamic = false)
    {
        return $this->getRepository()->getLeadsByList($lists, $idOnly, $dynamic);
    }
}