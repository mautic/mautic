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
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class FieldModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class FieldModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadField');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:fields';
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
            return new LeadField();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Returns lead custom fields
     *
     * @param $args
     *
     * @return array
     */
    public function getEntities(array $args = array())
    {
        return $this->em->getRepository('MauticLeadBundle:LeadField')->getEntities($args);
    }

    /**
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadEntity'));
        }

        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $alias = $entity->getAlias();

        if ($isNew) {
            if (empty($alias)) {
                $alias = strtolower(InputHelper::alphanum($entity->getName()));
            } else {
                $alias = strtolower(InputHelper::alphanum($alias));
            }

            //make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $aliases   = $repo->getAliases($entity->getId());
            $count     = (int)in_array($testAlias, $aliases);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias . $aliasTag;
                $count     = (int)in_array($testAlias, $aliases);
                $aliasTag++;
            }

            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        if ($entity->getType() == 'time') {
            //time does not work well with list filters
            $entity->setIsListable(false);
        }

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);

        if ($entity->getId()) {
            //create the field as its own column in the leads table
            $leadsSchema = $this->factory->getSchemaHelper('column', 'leads');
            if ($isNew || (!$isNew && !$leadsSchema->checkColumnExists($alias))) {
                $leadsSchema->addColumn(array(
                    'name' => $alias,
                    'type' => 'text',
                    'options' => array(
                        'notnull' => false
                    )
                ));
                $leadsSchema->executeChanges();
            }
        }

        //update order of other fields
        $this->reorderFieldsByEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @param  $entity
     */
    public function deleteEntity($entity)
    {
        parent::deleteEntity($entity);

        //remove the column from the leads table
        $leadsSchema = $this->factory->getSchemaHelper('column', 'leads');
        $leadsSchema->dropColumn($entity->getAlias());
        $leadsSchema->executeChanges();
    }

    /**
     * Delete an array of entities
     *
     * @param array $ids
     *
     * @return array
     */
    public function deleteEntities($ids)
    {
        $entities = parent::deleteEntities($ids);

        //remove the column from the leads table
        $leadsSchema = $this->factory->getSchemaHelper('column', 'leads');
        foreach ($entities as $e) {
            $leadsSchema->dropColumn($e->getAlias());
        }
        $leadsSchema->executeChanges();
    }

    /**
     * Reorder fields based on passed entity position
     *
     * @param $entity
     */
    public function reorderFieldsByEntity($entity)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadEntity'));
        }

        $fields = $this->getRepository()->findBy(array(), array('order' => 'ASC'));
        $count  = 1;
        $order  = $entity->getOrder();
        $id     = $entity->getId();
        $hit    = false;
        foreach ($fields as $field) {
            if ($id !== $field->getId()) {
                if ($order === $field->getOrder()) {
                    if ($hit) {
                        $field->setOrder($count - 1);
                    } else {
                        $field->setOrder($count + 1);
                    }
                } else {
                    $field->setOrder($count);
                }
                $this->em->persist($field);
            } else {
                $hit = true;
            }
            $count++;
        }
        $this->em->flush();
    }

    /**
     * Reorders fields by a list of field ids
     *
     * @param array $list
     */
    public function reorderFieldsByList(array $list)
    {
        $fields = $this->getRepository()->findBy(array(), array('order' => 'ASC'));
        foreach ($fields as $field) {
            if (in_array($field->getId(), $list)) {
                $order = ((int) array_search($field->getId(), $list) + 1);
                $field->setOrder($order);
                $this->em->persist($field);
            }
        }
        $this->em->flush();
    }

    /**
     * Get list of custom field values for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        return $this->em->getRepository('MauticLeadBundle:Lead')->getValueList($type, $filter, $limit);
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
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadField'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('leadfield', $entity, $params);
    }

    /**
     * @param $entity
     * @param properties
     * @return bool
     */
    public function setFieldProperties(&$entity, $properties)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadEntity'));
        }

        if (!empty($properties) && is_array($properties)) {
            $properties = InputHelper::clean($properties);
        } else {
            $properties = array();
        }

        //validate properties
        $type   = $entity->getType();
        $result = FormFieldHelper::validateProperties($type, $properties);
        if ($result[0]) {
            $entity->setProperties($properties);
            return true;
        } else {
            return $result[1];
        }
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
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadField'));
        }

        switch ($action) {
            case "pre_save":
                $name = LeadEvents::FIELD_PRE_SAVE;
                break;
            case "post_save":
                $name = LeadEvents::FIELD_POST_SAVE;
                break;
            case "pre_delete":
                $name = LeadEvents::FIELD_PRE_DELETE;
                break;
            case "post_delete":
                $name = LeadEvents::FIELD_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadFieldEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * @param bool $byGroup
     * @param bool $alphabetical
     *
     * @return array
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = array('isPublished' => true))
    {
        $forceFilters = array();
        foreach ($filters as $col => $val) {
            $forceFilters[] = array(
                'column' => "f.{$col}",
                'expr'   => 'eq',
                'value'  => $val
            );
        }
        // Get a list of custom form fields
        $fields = $this->getEntities(array(
            'filter'     => array(
                'force' => $forceFilters
            ),
            'orderBy'    => 'f.order',
            'orderByDir' => 'asc'
        ));

        $leadFields = array();

        foreach ($fields as $f) {
            if ($byGroup) {
                $fieldName = $this->translator->trans('mautic.lead.field.group.' . $f->getGroup());
                $leadFields[$fieldName][$f->getAlias()] = $f->getLabel();
            } else {
                $leadFields[$f->getAlias()] = $f->getLabel();
            }

        }

        if ($alphabetical) {
            // Sort the groups
            uksort($leadFields, 'strnatcmp');

            if ($byGroup) {
                // Sort each group by translation
                foreach ($leadFields as $group => &$fieldGroup) {
                    uasort($fieldGroup, 'strnatcmp');
                }
            }
        }

        return $leadFields;
    }

    /*
     * Retrieves a list of published fields that are unique identifers
     *
     * @return array
     */
    public function getUniqueIdentiferFields()
    {
        $filters = array ('isPublished' => true, 'isUniqueIdentifer' => true);

        $fields = $this->getFieldList(false, true,  $filters);

        return $fields;
    }
}