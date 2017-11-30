<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Exception\PermissionBadFormatException;
use Mautic\CoreBundle\Security\Exception\PermissionNotFoundException;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Security.
 */
class CorePermissions
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var array
     */
    private $pluginBundles;

    /**
     * @var array
     */
    private $permissionClasses = [];

    /**
     * @var array
     */
    private $permissionObjects = [];

    /**
     * @var array
     */
    private $grantedPermissions = [];

    /**
     * @var array
     */
    private $checkedPermissions = [];

    /**
     * CorePermissions constructor.
     *
     * @param Translator $translator
     * @param array      $parameters
     * @param            $bundles
     * @param            $pluginBundles
     */
    public function __construct(UserHelper $userHelper, TranslatorInterface $translator, array $parameters, $bundles, $pluginBundles)
    {
        $this->translator    = $translator;
        $this->params        = $parameters;
        $this->bundles       = $bundles;
        $this->pluginBundles = $pluginBundles;
        $this->userHelper    = $userHelper;

        $this->registerPermissionClasses();
    }

    /**
     * Retrieves each bundles permission objects.
     *
     * @return array
     */
    public function getPermissionObjects()
    {
        $objects = [];
        foreach ($this->permissionClasses as $key => $class) {
            if ($object = $this->getPermissionObject($key, false)) {
                $objects[] = $object;
            }
        }

        return $objects;
    }

    /**
     * Returns the bundles permission class object.
     *
     * @param string $bundle
     * @param bool   $throwException
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPermissionObject($bundle, $throwException = true)
    {
        if (!empty($bundle)) {
            if (isset($this->permissionClasses[$bundle])) {
                if (empty($this->permissionObjects[$bundle])) {
                    $permissionClass                  = $this->permissionClasses[$bundle];
                    $this->permissionObjects[$bundle] = new $permissionClass($this->getParams());
                }
            } else {
                if ($throwException) {
                    throw new \InvalidArgumentException("Permission class not found for {$bundle} in permissions classes");
                }

                return false;
            }

            return $this->permissionObjects[$bundle];
        }

        throw new \InvalidArgumentException("Bundle and permission type must be specified. '$bundle' given.");
    }

    /**
     * Generates the bit value for the bundle's permission.
     *
     * @param array $permissions
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function generatePermissions(array $permissions)
    {
        $entities = [];

        //give bundles an opportunity to analyze and adjust permissions based on others
        $classes = $this->getPermissionObjects();

        //bust out permissions into their respective bundles
        $bundlePermissions = [];
        foreach ($permissions as $permission => $perms) {
            list($bundle, $level)               = explode(':', $permission);
            $bundlePermissions[$bundle][$level] = $perms;
        }

        $bundles = array_keys($classes);

        foreach ($bundles as $bundle) {
            if (!isset($bundlePermissions[$bundle])) {
                $bundlePermissions[$bundle] = [];
            }
        }

        //do a first round to give bundles a chance to update everything and give an opportunity to require a second round
        //if the permission it is looking for from another bundle is not configured yet
        $secondRound = [];
        foreach ($classes as $bundle => $class) {
            $needsRoundTwo = $class->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions);
            if ($needsRoundTwo) {
                $secondRound[] = $bundle;
            }
        }

        foreach ($secondRound as $bundle) {
            $classes[$bundle]->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions, true);
        }

        //create entities
        foreach ($bundlePermissions as $bundle => $permissions) {
            foreach ($permissions as $name => $perms) {
                $entity = new Permission();
                $entity->setBundle($bundle);
                $entity->setName($name);

                $bit   = 0;
                $class = $this->getPermissionObject($bundle, true);

                foreach ($perms as $perm) {
                    //get the bit for the perm
                    if (!$class->isSupported($name, $perm)) {
                        throw new \InvalidArgumentException("$perm does not exist for $bundle:$name");
                    }

                    $bit += $class->getValue($name, $perm);
                }
                $entity->setBitwise($bit);
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->userHelper->getUser()->isAdmin();
    }

    /**
     * Determines if the user has permission to access the given area.
     *
     * @param array|string $requestedPermission
     * @param string       $mode                MATCH_ALL|MATCH_ONE|RETURN_ARRAY
     * @param User         $userEntity
     * @param bool         $allowUnknown        If the permission is not recognized, false will be returned.  Otherwise an
     *                                          exception will be thrown
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function isGranted($requestedPermission, $mode = 'MATCH_ALL', $userEntity = null, $allowUnknown = false)
    {
        if ($userEntity === null) {
            $userEntity = $this->userHelper->getUser();
        }

        if (!is_array($requestedPermission)) {
            $requestedPermission = [$requestedPermission];
        }

        $permissions = [];
        foreach ($requestedPermission as $permission) {
            if (isset($this->grantedPermissions[$permission])) {
                $permissions[$permission] = $this->grantedPermissions[$permission];
                continue;
            }

            $parts = explode(':', $permission);

            if ($parts[0] == 'plugin' && count($parts) == 4) {
                // @deprecated - no longer used; to be removed in 3.0
                array_shift($parts);
            }

            if (count($parts) != 3) {
                throw new PermissionBadFormatException(
                    $this->getTranslator()->trans(
                        'mautic.core.permissions.badformat',
                        ['%permission%' => $permission]
                    )
                );
            }

            if ($userEntity->isAdmin()) {
                //admin user has access to everything
                $permissions[$permission] = true;
            } else {
                $activePermissions = ($userEntity instanceof User) ? $userEntity->getActivePermissions() : [];

                //check against bundle permissions class
                $permissionObject = $this->getPermissionObject($parts[0]);

                //Is the permission supported?
                if (!$permissionObject->isSupported($parts[1], $parts[2])) {
                    if ($allowUnknown) {
                        $permissions[$permission] = false;
                    } else {
                        throw new PermissionNotFoundException(
                            $this->getTranslator()->trans(
                                'mautic.core.permissions.notfound',
                                ['%permission%' => $permission]
                            )
                        );
                    }
                } elseif ($userEntity == 'anon.') {
                    //anon user or session timeout
                    $permissions[$permission] = false;
                } elseif (!isset($activePermissions[$parts[0]])) {
                    //user does not have implicit access to bundle so deny
                    $permissions[$permission] = false;
                } else {
                    $permissions[$permission] = $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
                }
            }

            $this->grantedPermissions[$permission] = $permissions[$permission];
        }

        if ($mode == 'MATCH_ALL') {
            //deny if any of the permissions are denied
            return in_array(0, $permissions) ? false : true;
        } elseif ($mode == 'MATCH_ONE') {
            //grant if any of the permissions were granted
            return in_array(1, $permissions) ? true : false;
        } elseif ($mode == 'RETURN_ARRAY') {
            return $permissions;
        } else {
            throw new PermissionNotFoundException(
                $this->getTranslator()->trans(
                    'mautic.core.permissions.mode.notfound',
                    ['%mode%' => $mode]
                )
            );
        }
    }

    /**
     * Check if a permission or array of permissions exist.
     *
     * @param array|string $permission
     *
     * @return bool
     */
    public function checkPermissionExists($permission)
    {
        $checkPermissions = (!is_array($permission)) ? [$permission] : $permission;

        $result = [];
        foreach ($checkPermissions as $p) {
            if (isset($this->checkedPermissions[$p])) {
                $result[$p] = $this->checkedPermissions[$p];
                continue;
            }

            $parts = explode(':', $p);

            if ($parts[0] == 'plugin' && count($parts) == 4) {
                // @deprecated - no longer used; to be removed in 3.0
                array_shift($parts);
            }

            if (count($parts) != 3) {
                $result[$p] = false;
            } else {
                //check against bundle permissions class
                $permissionObject = $this->getPermissionObject($parts[0], false);
                $result[$p]       = $permissionObject && $permissionObject->isSupported($parts[1], $parts[2]);
            }
        }

        return (is_array($permission)) ? $result : $result[$permission];
    }

    /**
     * Checks if the user has access to the requested entity.
     *
     * @param string|bool $ownPermission
     * @param string|bool $otherPermission
     * @param User|int    $ownerId
     *
     * @return bool
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId = 0)
    {
        $user = $this->userHelper->getUser();
        if (!is_object($user)) {
            //user is likely anon. so assume no access and let controller handle via published status
            return false;
        }

        if ($ownerId instanceof User) {
            $ownerId = $ownerId->getId();
        }

        if (!is_bool($ownPermission) && !is_bool($otherPermission)) {
            $permissions = $this->isGranted(
                [$ownPermission, $otherPermission],
                'RETURN_ARRAY'
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

        $ownerId = (int) $ownerId;

        if ($ownerId === 0) {
            if ($other) {
                return true;
            } else {
                return false;
            }
        } elseif ($own && (int) $this->userHelper->getUser()->getId() === (int) $ownerId) {
            return true;
        } elseif ($other && (int) $this->userHelper->getUser()->getId() !== (int) $ownerId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves all permissions.
     *
     * @param bool $forJs
     *
     * @return array
     */
    public function getAllPermissions($forJs = false)
    {
        $permissionClasses = $this->getPermissionObjects();
        $permissions       = [];
        foreach ($permissionClasses as $object) {
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

    /**
     * @return bool
     */
    public function isAnonymous()
    {
        $userEntity = $this->userHelper->getUser();

        return ($userEntity instanceof User && !$userEntity->isGuest()) ? false : true;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return bool|mixed
     */
    protected function getBundles()
    {
        return $this->bundles;
    }

    /**
     * @return array
     */
    protected function getPluginBundles()
    {
        return $this->pluginBundles;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * Register permission classes.
     */
    private function registerPermissionClasses()
    {
        foreach ($this->getBundles() as $bundle) {
            if (!empty($bundle['permissionClasses'])) {
                $this->permissionClasses = array_merge($this->permissionClasses, $bundle['permissionClasses']);
            }
        }

        foreach ($this->getPluginBundles() as $bundle) {
            if (!empty($bundle['permissionClasses'])) {
                $this->permissionClasses = array_merge($this->permissionClasses, $bundle['permissionClasses']);
            }
        }
    }
}
