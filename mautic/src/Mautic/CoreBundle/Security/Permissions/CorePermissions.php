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
use Doctrine\ORM\EntityManager;
use Mautic\UserBundle\Entity\Permission;
//@TODO create new exception
use Symfony\Component\Debug\Exception\DummyException;
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
        $this->bundles   = array_keys($bundles);
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
                if ($bundle == "MauticCoreBundle")
                    continue; //do not include this file

                //explode MauticUserBundle into Mautic User Bundle so we can build the class needed
                $string     = preg_replace('/([a-z0-9])([A-Z])/', "$1 $2", $bundle);
                $namespace  = explode(' ', $string);
                $object     = $this->getPermissionObject($namespace[1], false);
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
            throw new DummyException("Bundle and permission type must be specified.");
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
                    throw new DummyException("$perm does not exist for $bundle:$name");
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
     * @param $bundle
     * @param $name
     * @param $level
     */
    public function isGranted ($requestedPermission) {
        $currentUser = $this->container->get('security.context')->getToken()->getUser();
        if ($currentUser->getRole()->isAdmin()) {
            //admin user has access to everything
            return 1;
        }

        $parts = explode(':', $requestedPermission);
        if (count($parts) != 3) {
            throw new DummyException("Permission must be in the format of bundle:permission:level (i.e. user:roles:view). $requestedPermission given.");
        }
        $activePermissions = $currentUser->getActivePermissions();

        //ensure consistency by forcing lowercase
        array_walk($parts, function(&$v) { $v = strtolower($v); });

        //check to see if the user has implicit access to bundle
        if (!isset($activePermissions[$parts[0]])) {
            //user does not have implicit access to bundle so deny
            return 0;
        }

        //check against bundle permissions class
        $permissionObject = $this->getPermissionObject($parts[0]);
        return $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
    }
}