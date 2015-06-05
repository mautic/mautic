<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Model;

use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\CategoryEvents;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CategoryModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */

class CategoryModel extends FormModel
{
    public function getRepository()
    {
        return $this->em->getRepository('MauticCategoryBundle:Category');
    }

    public function getNameGetter()
    {
        return "getTitle";
    }

    public function getPermissionBase()
    {
        $request = $this->factory->getRequest();
        $bundle  = $request->get('bundle');
        return $bundle.':categories';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getTitle(), false, '-'));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, false, '-'));
        }

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $bundle    = $entity->getBundle();
        $count     = $repo->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $count     = $repo->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
            $aliasTag++;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        parent::saveEntity($entity, $unlock);
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
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(array('Category'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create('category_form', $entity, $options);
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
            return new Category();
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
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(array('Category'));
        }

        switch ($action) {
            case "pre_save":
                $name = CategoryEvents::CATEGORY_PRE_SAVE;
                break;
            case "post_save":
                $name = CategoryEvents::CATEGORY_POST_SAVE;
                break;
            case "pre_delete":
                $name = CategoryEvents::CATEGORY_PRE_DELETE;
                break;
            case "post_delete":
                $name = CategoryEvents::CATEGORY_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new CategoryEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return null;
        }
    }

    /**
     * Delete an entity
     *
     * @param  $entity
     * @return null|object
     */
    public function deleteEntity($entity)
    {
        $bundle = $entity->getBundle();

        //if it doesn't have a dot, then assume the model will be $bundle.$bundle
        $modelName = (strpos($bundle, '.') === false) ? $bundle.'.'.$bundle : $bundle;
        $model     = $this->factory->getModel($modelName);

        $repo       = $model->getRepository();
        $tableAlias = $repo->getTableAlias();

        $entities = $model->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => $tableAlias.'.category',
                        'expr'   => 'eq',
                        'value'  => $entity->getId()
                    )
                )
            )
        ));

        if (!empty($entities)) {
            foreach ($entities as $e) {
                $e->setCategory(null);
            }
            $model->saveEntities($entities, false);
        }

        parent::deleteEntity($entity);
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $bundle
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($bundle, $filter = '', $limit = 10)
    {
        $results = $this->getRepository()->getCategoryList($bundle, $filter, $limit, 0);
        return $results;
    }
}