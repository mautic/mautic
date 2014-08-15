<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Category;
use Mautic\PageBundle\PageEvents;

/**
 * Class CategoryModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */

class CategoryModel extends FormModel
{
    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Category');
    }

    public function getPermissionBase()
    {
        return 'page:categories';
    }

    public function getNameGetter()
    {
        return "getTitle";
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
            $alias = strtolower(InputHelper::alphanum($entity->getTitle(), true));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, true));
        }

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $count     = $repo->checkUniqueAlias($testAlias, $entity);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
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
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('pagecategory', $entity, $params);
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
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(array('Category'));
        }

        switch ($action) {
            case "pre_save":
                $name = PageEvents::CATEGORY_PRE_SAVE;
                break;
            case "post_save":
                $name = PageEvents::CATEGORY_POST_SAVE;
                break;
            case "pre_delete":
                $name = PageEvents::CATEGORY_PRE_DELETE;
                break;
            case "post_delete":
                $name = PageEvents::CATEGORY_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
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
        //uncategorize pages
        $pages = $entity->getPages();
        foreach ($pages as $page) {
            $page->setCategory(null);
        }
        $this->factory->getModel('page.page')->saveEntities($pages);

        parent::deleteEntity($entity);
    }

}