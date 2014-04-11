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
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;

/**
 * Class FormModel
 *
 * @package Mautic\CoreBundle\Model
 */
class FormModel
{

    protected $container;
    protected $request;
    protected $em;

    /**
     * @param Container     $container
     * @param RequestStack  $request_stack
     * @param EntityManager $em
     */
    public function __construct(Container $container, RequestStack $request_stack, EntityManager $em)
    {
        $this->container = $container;
        $this->request   = $request_stack->getCurrentRequest();
        $this->em        = $em;
    }

    /**
     * Create/edit entity
     *
     * @param       $entity
     * @param bool  $isNew
     * @param array $overrides
     * @return int
     */
    public function saveEntity($entity, $isNew = false, $overrides = array())
    {
        //@TODO add catch to determine editown or editother
        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic.security')->isGranted($this->permissionBase . ':' . $permissionNeeded)) {
            //@TODO add error message
            return 0;
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
    public function deleteEntity($entityId, $skipSecurity = false)
    {
        //@TODO add catch to determine deleteown or deleteother
        if (!$skipSecurity && !$this->container->get('mautic.security')->isGranted($this->permissionBase . ':deleteother')) {
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