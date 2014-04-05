<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

/**
 * Class FormModel
 *
 * @package Mautic\CoreBundle\Model
 */
class FormModel
{

    protected $request;
    protected $container;
    protected $em;

    /**
     * @param Container     $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em)
    {
        $this->request   = $container->get('request');
        $this->container = $container;
        $this->em        = $em;
    }

    /**
     * Create/edit entity
     *
     * @param      $entity
     * @param bool $isNew
     * @return int
     */
    public function saveEntity($entity, $isNew = false)
    {
        //@TODO add catch to determine editown or editother
        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic_core.permissions')->isGranted($this->permissionBase . ':' . $permissionNeeded)) {
            //@TODO add error message
            return 0;
        }

        //set the date/time for new submission
        if (method_exists($entity, 'setDateAdded')) {
            $entity->setDateAdded(new \DateTime());
        }

        return $this->em->getRepository($this->repository)->saveEntity($entity);
    }

    /**
     * Delete an entity
     *
     * @param $entityId
     * @return int|null|object
     */
    public function deleteEntity($entityId)
    {
        //@TODO add catch to determine deleteown or deleteother
        if (!$this->container->get('mautic_core.permissions')->isGranted($this->permissionBase . ':deleteother')) {
            //@TODO add error message
            return 0;
        }

        try {
            $entity = $this->em->getRepository($this->repository)->find($entityId);
            return ($this->em->getRepository($this->repository)->deleteEntity($entity)) ? $entity : 0;
        } catch (\Exception $e) {
            //@TODO return error message
            return 0;
        }
    }
}