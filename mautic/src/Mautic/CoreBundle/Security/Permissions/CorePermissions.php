<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;
use Mautic\UserBundle\Entity\Permission;

/**
 * Class CorePermissions
 *
 * @package Mautic\CoreBundle\Security\Permissions
 */
class CorePermissions {

    /**
     * @var
     */
    private $container;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var
     */
    private $em;

    /**
     * @param Container $container
     * @param array     $bundles
     */
    public function __construct(Container $container, EntityManager $em, array $bundles) {
        $this->container = $container;
        $this->em        = $em;
        $this->bundles   = $bundles;
    }

    /**
     * Retrieves each bundles permission objects
     *
     * @return array
     */
    public function getPermissionObjects() {
        static $classes = array();
        if (empty($classes)) {
            foreach ($this->bundles as $bundle) {
                if ($bundle['base'] == "Core")
                    continue; //do not include this file

                //explode MauticUserBundle into Mautic User Bundle so we can build the class needed
                $object     = $this->getPermissionObject($bundle['base'], false);
                if (!empty($object)) {
                    $classes[] = $object;
                }
            }
        }
        return $classes;
    }

    /**
     * Returns the bundles permission class object
     *
     * @param      $bundle
     * @param bool $throwException
     * @return mixed
     * @throws \Symfony\Component\Debug\Exception\DummyException
     */
    public function getPermissionObject($bundle, $throwException = true) {
        static $classes = array();
        if (!empty($bundle)) {
            if (empty($classes[$bundle])) {
                $bundle    = ucfirst($bundle);
                $className = "Mautic\\{$bundle}Bundle\\Security\\Permissions\\{$bundle}Permissions";
                if (class_exists($className)) {
                    $classes[$bundle] = new $className($this->container, $this->em);
                } elseif ($throwException) {
                    throw new DummyException("$className not found!");
                } else {
                    $classes[$bundle] = false;
                }
            }

            return $classes[$bundle];
        } else {
            throw new NotFoundHttpException("Bundle and permission type must be specified. '$bundle' given.");
        }
    }

    /**
     * Generates the bit value for the bundle's permission
     *
     * @param array $permissions
     * @return array
     * @throws \Symfony\Component\Debug\Exception\DummyException
     */
    public function generatePermissions(array $permissions) {
        $entities = array();

        foreach($permissions as $key => $perms) {
            list($bundle, $name) = explode(":", $key);

            $entity = new Permission();

            //strtolower to ensure consistency
            $entity->setBundle(strtolower($bundle));
            $entity->setName(strtolower($name));

            $bit = 0;
            foreach ($perms as $perm) {
                //get the bit for the perm
                $class = $this->getPermissionObject($bundle);
                if ($supports = $class->isSupported($name, $perm)) {
                    $bit += $class->getValue($name, $perm);
                } else {
                    throw new NotFoundHttpException("$perm does not exist for $bundle:$name");
                }
            }
            $entity->setBitwise($bit);
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * Determines if the user has permission to access the given area
     *
     * @param      $requestedPermission
     * @param null $userEntity
     * @return int
     * @throws \Symfony\Component\Debug\Exception\NotFoundHttpException
     */
    public function isGranted ($requestedPermission, $userEntity = null)
    {
        if ($this->container->get('security.context')->getToken() === null) {
            throw new NotFoundHttpException('No security context found.');
        }

        if ($userEntity === null) {
           $userEntity = $this->container->get('security.context')->getToken()->getUser();
        }

        if ($userEntity->getRole()->isAdmin()) {
            //admin user has access to everything
            return 1;
        }

        $parts = explode(':', $requestedPermission);
        if (count($parts) != 3) {
            throw new NotFoundHttpException($this->container->get('translator')->trans('mautic.core.permissions.notfound',
                array("requested" => $requestedPermission))
            );
        }
        $activePermissions = $userEntity->getActivePermissions();

        //ensure consistency by forcing lowercase
        array_walk($parts, function(&$v) { $v = strtolower($v); });

        //check against bundle permissions class
        $permissionObject = $this->getPermissionObject($parts[0]);

        //Is the permission supported?
        if (!$permissionObject->isSupported($parts[1], $parts[2])) {
            throw new NotFoundHttpException($this->container->get('translator')->trans('mautic.core.permissions.notfound',
                array("requested" => $requestedPermission))
            );
        }

        //check to see if the user has implicit access to bundle
        if (!isset($activePermissions[$parts[0]])) {
            //user does not have implicit access to bundle so deny
            return 0;
        }

        return $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
    }
}