<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Doctrine\ORM\EntityManager;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CorePermissions
 *
 * @package Mautic\CoreBundle\Security\Permissions
 */
class CorePermissions {

    /**
     * @var
     */
    private $translator;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var
     */
    private $em;

    /**
     * @var array
     */
    private $params;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $security;

    public function __construct(TranslatorInterface $translator, EntityManager $em, SecurityContext $security, array $bundles, array $params)
    {
        $this->translator = $translator;
        $this->em         = $em;
        $this->bundles    = $bundles;
        $this->security   = $security;
        $this->params     = $params;
    }

    /**
     * Retrieves each bundles permission objects
     *
     * @return array
     */
    public function getPermissionClasses() {
        static $classes = array();
        if (empty($classes)) {
            foreach ($this->bundles as $bundle) {
                if ($bundle['base'] == "Core")
                    continue; //do not include this file

                //explode MauticUserBundle into Mautic User Bundle so we can build the class needed
                $object     = $this->getPermissionClass($bundle['base'], false);
                if (!empty($object)) {
                    $classes[strtolower($bundle['base'])] = $object;
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
    public function getPermissionClass($bundle, $throwException = true) {
        static $classes = array();
        if (!empty($bundle)) {
            if (empty($classes[$bundle])) {
                $bundle    = ucfirst($bundle);
                $className = "Mautic\\{$bundle}Bundle\\Security\\Permissions\\{$bundle}Permissions";
                if (class_exists($className)) {
                    $classes[$bundle] = new $className($this->params);
                } elseif ($throwException) {
                    throw new \InvalidArgumentException("$className not found!");
                } else {
                    $classes[$bundle] = false;
                }
            }

            return $classes[$bundle];
        } else {
            throw new \InvalidArgumentException("Bundle and permission type must be specified. '$bundle' given.");
        }
    }

    /**
     * Generates the bit value for the bundle's permission
     *
     * @param array $permissions
     * @return array
     * @throws \InvalidArgumentException
     */
    public function generatePermissions(array $permissions) {
        $entities = array();

        //give bundles an opportunity to analyze and adjust permissions based on others
        $classes = $this->getPermissionClasses();
        foreach ($classes as $class) {
            $class->analyzePermissions($permissions);
        }

        //create entities
        foreach($permissions as $key => $perms) {
            list($bundle, $name) = explode(":", $key);

            $entity = new Permission();

            //strtolower to ensure consistency
            $entity->setBundle(strtolower($bundle));
            $entity->setName(strtolower($name));

            $bit = 0;
            $class = $this->getPermissionClass($bundle);

            foreach ($perms as $perm) {
                //get the bit for the perm
                if ($supports = $class->isSupported($name, $perm)) {
                    $bit += $class->getValue($name, $perm);
                } else {
                    throw new \InvalidArgumentException("$perm does not exist for $bundle:$name");
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
     * @throws \InvalidArgumentException
     */
    public function isGranted ($requestedPermission, $mode = "MATCH_ALL", $userEntity = null)
    {
        if ($userEntity === null) {
           $userEntity = $this->getUser();
        }

        if (!is_array($requestedPermission)) {
            $requestedPermission = array($requestedPermission);
        }

        $permissions = array();
        foreach ($requestedPermission as $permission) {
            $parts = explode(':', $permission);
            if (count($parts) != 3) {
                throw new \InvalidArgumentException($this->translator->trans('mautic.core.permissions.badformat',
                        array("%permission%" => $permission))
                );
            }

            $activePermissions = $userEntity->getActivePermissions();

            //ensure consistency by forcing lowercase
            array_walk($parts, function (&$v) {
                    $v = strtolower($v);
                });

            //check against bundle permissions class
            $permissionObject = $this->getPermissionClass($parts[0]);

            //Is the permission supported?
            if (!$permissionObject->isSupported($parts[1], $parts[2])) {
                throw new \InvalidArgumentException($this->translator->trans('mautic.core.permissions.notfound',
                        array("%permission%" => $permission))
                );
            }

            if ($userEntity->isAdmin()) {
                //admin user has access to everything
                $permissions[$permission] = true;
            } elseif (!isset($activePermissions[$parts[0]])) {
                //user does not have implicit access to bundle so deny
                $permissions[$permission] = false;
            } else {
                $permissions[$permission] = $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
            }
        }

        if ($mode == "MATCH_ALL") {
            //deny if any of the permissions are denied
            return in_array(0, $permissions) ? false : true;
        } elseif ($mode == "MATCH_ONE") {
            //grant if any of the permissions were granted
            return in_array(1, $permissions) ? true : false;
        } elseif ($mode == "RETURN_ARRAY") {
            return $permissions;
        } else {
            throw new \InvalidArgumentException($this->translator->trans('mautic.core.permissions.mode.notfound',
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
        if (!is_bool($ownPermission) && !is_bool($otherPermission)) {
            $permissions = $this->isGranted(
                array($ownPermission, $otherPermission), 'RETURN_ARRAY'
            );

            $own   = $permissions[$ownPermission];
            $other = $permissions[$otherPermission];
        } else {
            if (!is_bool($ownPermission)) {
                $own = $this->isGranted($ownPermission);
            } else {
                $own = $ownPermission;
            }

            if (!is_bool($otherPermission)) {
                $other = $this->isGranted($otherPermission);
            } else {
                $other = $otherPermission;
            }
        }

        $ownerId = (!empty($owner)) ? $owner->getId() : 0;

        if ($ownerId === 0) {
            if ($other) {
                return true;
            } else {
                return false;
            }
        } elseif ($own && (int) $this->getUser()->getId() === (int) $ownerId) {
            return true;
        } elseif ($other && (int) $this->getUser()->getId() !== (int) $ownerId) {
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
        $permissionObjects = $this->getPermissionClasses();
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

    private function getUser()
    {
        if ($token = $this->security->getToken()) {
            $this->user = $token->getUser();
        } else {
            $this->user = new User();
        }
        return $this->user;
    }
}