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
        $repo = $this->em->getRepository($this->repository);
        if (method_exists($repo, 'getEntity')) {
            return $repo->getEntity($id);
        } else {
            return $repo->find($id);
        }
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
        $this->em->getRepository($this->repository)->setCurrentUser(
            $this->container->get('security.context')->getToken()->getUser()
        );

        return $this->em
            ->getRepository($this->repository)
            ->getEntities($args);
    }

    /**
     * Create/edit entity
     *
     * @param       $entity
     * @return mixed
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function saveEntity($entity)
    {
        $isNew = ($entity->getId()) ? false : true;

        //set the date/time for new submission
        if (method_exists($entity, 'setDateAdded') && !$entity->getDateAdded()) {
            $entity->setDateAdded(new \DateTime());
        }

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->em->getRepository($this->repository)->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);

        return $entity;
    }

    /**
     * Delete an entity
     *
     * @param      $entityId
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteEntity($entityId)
    {
        $entity = $this->em->getRepository($this->repository)->find($entityId);

        $event = $this->dispatchEvent("pre_delete", $entity);
        $this->em->getRepository($this->repository)->deleteEntity($entity);
        $this->dispatchEvent("post_delete", $entity, $event);

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
     * @param $event
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        //...
    }
}