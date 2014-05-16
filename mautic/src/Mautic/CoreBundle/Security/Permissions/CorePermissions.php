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
use Symfony\Component\Security\Core\SecurityContext;

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
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $securityContext;

    /**
     * @param Container $container
     * @param array     $bundles
     */
    public function __construct(Container $container, EntityManager $em, array $bundles, SecurityContext $securityContext) {
        $this->container       = $container;
        $this->em              = $em;
        $this->bundles         = $bundles;
        $this->securityContext = $securityContext;
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
     * @throws \Symfony\Component\Debug\Exception\NotFoundHttpException
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
                    throw new NotFoundHttpException("$className not found!");
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
     * @throws \Symfony\Component\Debug\Exception\NotFoundHttpException
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
            $class = $this->getPermissionObject($bundle);
            $perms = $class->analyzePermissions($name, $perms);

            foreach ($perms as $perm) {
                //get the bit for the perm
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
     * @param bool $mode MATCH_ALL|MATCH_ONE|RETURN_ARRAY
     * @param null $userEntity
     * @return bool
     * @throws NotFoundHttpException
     */
    public function isGranted ($requestedPermission, $mode = "MATCH_ALL", $userEntity = null)
    {
        if ($this->container->get('security.context')->getToken() === null) {
            throw new NotFoundHttpException('No security context found.');
        }

        if ($userEntity === null) {
           $userEntity = $this->container->get('security.context')->getToken()->getUser();
        }

        if (!is_array($requestedPermission)) {
            $requestedPermission = array($requestedPermission);
        }

        $permissions = array();
        foreach ($requestedPermission as $permission) {
            $parts = explode(':', $permission);
            if (count($parts) != 3) {
                throw new NotFoundHttpException($this->container->get('translator')->trans('mautic.core.permissions.badformat',
                        array("%permission%" => $permission))
                );
            }

            $activePermissions = $userEntity->getActivePermissions();

            //ensure consistency by forcing lowercase
            array_walk($parts, function (&$v) {
                    $v = strtolower($v);
                });

            //check against bundle permissions class
            $permissionObject = $this->getPermissionObject($parts[0]);

            //Is the permission supported?
            if (!$permissionObject->isSupported($parts[1], $parts[2])) {
                throw new NotFoundHttpException($this->container->get('translator')->trans('mautic.core.permissions.notfound',
                        array("%permission%" => $permission))
                );
            }

            if ($userEntity->getRole()->isAdmin()) {
                //admin user has access to everything
                $permissions[$permission] = 1;
            } elseif (!isset($activePermissions[$parts[0]])) {
                //user does not have implicit access to bundle so deny
                $permissions[$permission] = 0;
            } else {
                $permissions[$permission] = $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
            }
        }

        if ($mode == "MATCH_ALL") {
            //deny if any of the permissions are denied
            return in_array(0, $permissions) ? 0 : 1;
        } elseif ($mode == "MATCH_ONE") {
            //grant if any of the permissions were granted
            return in_array(1, $permissions) ? 1 : 0;
        } elseif ($mode == "RETURN_ARRAY") {
            return $permissions;
        } else {
            throw new NotFoundHttpException($this->container->get('translator')->trans('mautic.core.permissions.mode.notfound',
                    array("%mode%" => $mode))
            );
        }
    }


    /**
     * Checks if the user has access to the requested entity
     *
     * @param $ownPermission
     * @param $otherPermission
     * @param $owner
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $owner)
    {
        $permissions = $this->isGranted(
            array($ownPermission, $otherPermission), 'RETURN_ARRAY'
        );

        $ownerId = (!empty($owner)) ? $owner->getId() : 0;

        $me = $this->securityContext->getToken()->getUser();
        if ($ownerId === 0) {
            if ($permissions[$otherPermission]) {
                return true;
            } else {
                return false;
            }
        } elseif ($permissions[$ownPermission] && (int) $me->getId() === (int) $ownerId) {
            return true;
        } elseif ($permissions[$otherPermission] && (int) $me->getId() !== (int) $ownerId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves all permissions
     *
     * @param  boolean  $forJs
     * @return array
     */
    public function getAllPermissions($forJs = false)
    {
        $permissionObjects = $this->getPermissionObjects();
        $permissions = array();
        foreach ($permissionObjects as $object) {
            $perms = $object->getPermissions();
            if ($forJs) {
                foreach ($perms as $level => $perm) {
                    $levelPerms = array_keys($perm);
                    $object->parseForJavascript($levelPerms);
                    $permissions[$object->getName()][$level] = $levelPerms;
                }
            } else {
                $permissions[$object->getName()] = $perms;
            }

        }
        return $permissions;
    }
}