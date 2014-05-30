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
use Mautic\CoreBundle\Model\CommonFormModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldValue;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class LeadFieldModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\CommonFormModel
 */
class LeadFieldModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository = 'MauticLeadBundle:LeadField';
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
     * @param $args Takes only key filter and must be array supported by EntityRepository::findBy
     *
     * @return array
     */
    public function getEntities(array $args = array())
    {
        $filter = (!empty($args['filter'])) ? $args['filter'] : array();
        return $this->em->getRepository('MauticLeadBundle:LeadField')->findBy($filter, array('order'=>'asc'));
    }

    /**
     * @param       $entity
     * @return mixed
     * @throws AccessDeniedException
     */
    public function saveEntity($entity)
    {
        if (!$entity instanceof LeadField && !$entity instanceof LeadFieldValue) {
            throw new MethodNotAllowedHttpException(array('LeadEntity', 'LeadFieldEntity'), 'Entity must be of type LeadField or LeadFieldValue');
        }

        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew);

        if ($entity instanceof LeadField) {

            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = strtolower(InputHelper::alphanum($entity->getName()));
            } else {
                $alias = strtolower(InputHelper::alphanum($alias));
            }

            //make sure alias is not already taken
            $testAlias = $alias;
            $count     = $this->em->getRepository('MauticLeadBundle:LeadField')->checkUniqueAlias($alias, $entity->getId());
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias . $aliasTag;
                $count     = $this->em->getRepository('MauticLeadBundle:LeadField')->checkUniqueAlias($testAlias, $entity->getId());
                $aliasTag++;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->em->getRepository($this->repository)->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);

        //update order of other fields
        $this->reorderFieldsByEntity($entity);

        return $entity;
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

        $fields = $this->em->getRepository($this->repository)->findBy(array(), array('order' => 'ASC'));
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
        $fields = $this->em->getRepository($this->repository)->findBy(array(), array('order' => 'ASC'));
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
        return $this->em->getRepository('MauticLeadBundle:LeadFieldValue')->getValueList($type, $filter, $limit);
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $action = null)
    {
        if (!$entity instanceof LeadField) {
            throw new MethodNotAllowedHttpException(array('LeadField'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $this->container->get('form.factory')->create('leadfield', $entity, $params);
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

        if (empty($event)) {
            $event = new LeadFieldEvent($entity, $isNew);
            $event->setEntityManager($this->em);
        }
        $dispatcher = $this->container->get('event_dispatcher');
        switch ($action) {
            case "pre_save":
                $dispatcher->dispatch(LeadEvents::FIELD_PRE_SAVE, $event);
                break;
            case "post_save":
                $dispatcher->dispatch(LeadEvents::FIELD_POST_SAVE, $event);
                break;
            case "pre_delete":
                $dispatcher->dispatch(LeadEvents::FIELD_PRE_DELETE, $event);
                break;
            case "post_delete":
                $dispatcher->dispatch(LeadEvents::FIELD_POST_DELETE, $event);
                break;
        }

        return $event;
    }
}