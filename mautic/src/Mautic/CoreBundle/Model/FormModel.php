<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Helper\SearchStringHelper;
use Symfony\Component\HttpKernel\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class FormModel
 *
 * @package Mautic\CoreBundle\Model
 */
class FormModel extends CommonModel
{

    /**
     * Get a specific entity
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = '')
    {
        return $this->em->getRepository($this->repository)->find($id);
    }

    /**
     * Return list of entities
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     * @return mixed
     */
    public function getEntities(array $args = array())
    {
        //set the translator
        $this->em->getRepository($this->repository)->setTranslator($this->container->get('translator'));

        return $this->em
            ->getRepository($this->repository)
            ->getEntities($args);
    }

    /**
     * Create/edit entity
     *
     * @param       $entity
     * @param array $overrides
     * @return mixed
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function saveEntity($entity, $overrides = array())
    {
        //@TODO add catch to determine editown or editother
        $isNew = ($entity->getId()) ? false : true;
        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic.security')->isGranted($this->permissionBase . ':' . $permissionNeeded)) {
            throw new AccessDeniedException($this->container->get('translator')->trans('mautic.core.accessdenied'));
        }

        if (!empty($overrides)) {
            foreach ($overrides as $k => $v) {
                if ($k == "entities") {
                    foreach ($v as $entityKey => $entityArray) {
                        $func = "add" . ucfirst($entityKey);
                        foreach ($entityArray as $e) {
                            $entity->$func($e);
                        }
                    }
                } else {
                    $func = "set" . ucfirst($k);
                    $entity->$func($v);
                }
            }
        }

        //set the date/time for new submission
        if (method_exists($entity, 'setDateAdded') && !$entity->getDateAdded()) {
            $entity->setDateAdded(new \DateTime());
        }

        $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->em->getRepository($this->repository)->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew);

        return $entity;
    }

    /**
     * Delete an entity
     *
     * @param      $entityId
     * @param bool $skipSecurity
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteEntity($entityId, $skipSecurity = false)
    {
        //@TODO add catch to determine deleteown or deleteother
        if (!$skipSecurity && !$this->container->get('mautic.security')->isGranted($this->permissionBase . ':deleteother')) {
            throw new AccessDeniedException($this->container->get('translator')->trans('mautic.core.accessdenied'));
        }

        $entity = $this->em->getRepository($this->repository)->find($entityId);

        //Event must be called first in order for getId() to be available for events
        $this->dispatchEvent("delete", $entity);

        $this->em->getRepository($this->repository)->deleteEntity($entity);

        return $entity;
    }

    /**
     * Creates the appropriate form per the model
     *
     * @param      $entity
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    public function createForm($entity, $action = null)
    {
        throw new NotFoundHttpException('Form object not found.');
    }

    /**
     * Dispatches events for child classes
     *
     * @param $action
     * @param $entity
     * @param $isNew
     */
    protected function dispatchEvent($action, &$entity, $isNew = false)
    {
        //...
    }
}