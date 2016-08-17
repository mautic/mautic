<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CorePermissions
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
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

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
     * CorePermissions constructor.
     *
     * @param Translator            $translator
     * @param EntityManager         $em
     * @param TokenStorageInterface $tokenStorage
     * @param array                 $parameters
     * @param                       $bundles
     * @param                       $pluginBundles
     */
    public function __construct(UserHelper $userHelper, TranslatorInterface $translator, EntityManager $em, TokenStorageInterface $tokenStorage, array $parameters, $bundles, $pluginBundles)
    {
        $this->translator    = $translator;
        $this->em            = $em;
        $this->tokenStorage  = $tokenStorage;
        $this->params        = $parameters;
        $this->bundles       = $bundles;
        $this->pluginBundles = $pluginBundles;
        $this->userHelper    = $userHelper;
    }

    /**
     * Retrieves each bundles permission objects
     *
     * @return array
     */
    public function getPermissionObjects()
    {
        static $classes = array();
        if (empty($classes)) {
            foreach ($this->getBundles() as $bundle) {
                $object = $this->getPermissionObject($bundle['base'], false);
                if (!empty($object)) {
                    $classes[strtolower($bundle['base'])] = $object;
                }
            }

            foreach ($this->getPluginBundles() as $bundle) {
                $object = $this->getPermissionObject($bundle['base'], false, true);
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
     * @param string $bundle
     * @param bool   $throwException
     * @param bool   $pluginBundle
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getPermissionObject($bundle, $throwException = true, $pluginBundle = false)
    {
        static $classes = array();
        if (!empty($bundle)) {
            if (empty($classes[$bundle])) {
                $bundle       = ucfirst($bundle);
                $checkBundles = ($pluginBundle) ? $this->getPluginBundles() : $this->getBundles();
                $bundleName   = $bundle . 'Bundle';

                if (!$pluginBundle) {
                    // Core bundle
                    $bundleName = 'Mautic' . $bundleName;
                }

                if ($bundle == 'Core') {
                    $className = $checkBundles[$bundleName]['namespace'] . "\\Security\\Permissions\\SystemPermissions";
                    $exists    = class_exists($className);
                } elseif (array_key_exists($bundleName, $checkBundles)) {
                    $className = $checkBundles[$bundleName]['namespace'] . "\\Security\\Permissions\\{$bundle}Permissions";
                    $exists    = class_exists($className);
                } else {
                    $exists = false;
                }

                if ($exists) {
                    $classes[$bundle] = new $className($this->getParams());
                } else {
                    if ($throwException) {
                        throw new \InvalidArgumentException("Permission class not found for {$bundle}Bundle!");
                    } else {
                        $classes[$bundle] = false;
                    }
                }
            }

            return $classes[$bundle];
        }

        throw new \InvalidArgumentException("Bundle and permission type must be specified. '$bundle' given.");
    }

    /**
     * Generates the bit value for the bundle's permission
     *
     * @param array $permissions
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function generatePermissions(array $permissions)
    {
        $entities = array();

        //give bundles an opportunity to analyze and adjust permissions based on others
        $classes = $this->getPermissionObjects();

        //bust out permissions into their respective bundles
        $bundlePermissions = array();
        foreach ($permissions as $permission => $perms) {
            list ($bundle, $level) = explode(':', $permission);
            $bundlePermissions[$bundle][$level] = $perms;
        }

        $bundles = array_keys($classes);

        foreach ($bundles as $bundle) {
            if (!isset($bundlePermissions[$bundle])) {
                $bundlePermissions[$bundle] = array();
            }
        }

        //do a first round to give bundles a chance to update everything and give an opportunity to require a second round
        //if the permission it is looking for from another bundle is not configured yet
        $secondRound = array();
        foreach ($classes as $bundle => $class) {
            $needsRoundTwo = $class->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions);
            if ($needsRoundTwo) {
                $secondRound[] = $bundle;
            }
        }

        foreach ($secondRound as $bundle) {
            $classes[$bundle]->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions, true);
        }

        //get a list of plugin bundles so we can tell later if a bundle is core or plugin
        $pluginBundles = $this->getPluginBundles();

        //create entities
        foreach ($bundlePermissions as $bundle => $permissions) {
            foreach ($permissions as $name => $perms) {
                $entity = new Permission();

                //strtolower to ensure consistency
                $entity->setBundle(strtolower($bundle));
                $entity->setName(strtolower($name));

                $bit   = 0;
                $class = $this->getPermissionObject($bundle, true, array_key_exists(ucfirst($bundle) . "Bundle", $pluginBundles));

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
     * Determines if the user has permission to access the given area
     *
     * @param array|string $requestedPermission
     * @param string       $mode MATCH_ALL|MATCH_ONE|RETURN_ARRAY
     * @param User         $userEntity
     * @param bool         $allowUnknown If the permission is not recognized, false will be returned.  Otherwise an
     *                                     exception will be thrown
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function isGranted($requestedPermission, $mode = "MATCH_ALL", $userEntity = null, $allowUnknown = false)
    {
        static $grantedPermissions = array();

        if ($userEntity === null) {
            $userEntity = $this->userHelper->getUser();
        }

        if (!is_array($requestedPermission)) {
            $requestedPermission = array($requestedPermission);
        }

        $permissions = array();
        foreach ($requestedPermission as $permission) {
            if (isset($grantedPermissions[$permission])) {
                $permissions[$permission] = $grantedPermissions[$permission];
                continue;
            }

            $parts = explode(':', $permission);

            if ($parts[0] == 'plugin' && count($parts) == 4) {
                $isPlugin = true;
                array_shift($parts);
            } else {
                $isPlugin = false;
            }

            if (count($parts) != 3) {
                throw new \InvalidArgumentException($this->getTranslator()->trans('mautic.core.permissions.badformat',
                    array("%permission%" => $permission))
                );
            }

            $activePermissions =  ($userEntity instanceof User) ? $userEntity->getActivePermissions() : array();

            //check against bundle permissions class
            $permissionObject = $this->getPermissionObject($parts[0], true, $isPlugin);

            //Is the permission supported?
            if (!$permissionObject->isSupported($parts[1], $parts[2])) {
                if ($allowUnknown) {
                    $permissions[$permission] = false;
                } else {
                    throw new \InvalidArgumentException($this->getTranslator()->trans('mautic.core.permissions.notfound',
                        array("%permission%" => $permission))
                    );
                }
            }elseif ($userEntity == "anon.") {
                //anon user or session timeout
                $permissions[$permission] = false;
            } elseif ($userEntity->isAdmin()) {
                //admin user has access to everything
                $permissions[$permission] = true;
            } elseif (!isset($activePermissions[$parts[0]])) {
                //user does not have implicit access to bundle so deny
                $permissions[$permission] = false;
            } else {
                $permissions[$permission] = $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
            }

            $grantedPermissions[$permission] = $permissions[$permission];
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
            throw new \InvalidArgumentException($this->getTranslator()->trans('mautic.core.permissions.mode.notfound',
                array("%mode%" => $mode))
            );
        }
    }

    /**
     * Check if a permission or array of permissions exist
     *
     * @param array|string $permission
     *
     * @return bool
     */
    public function checkPermissionExists($permission)
    {
        static $checkedPermissions = array();

        $checkPermissions = (!is_array($permission)) ? array($permission) : $permission;

        $result = array();
        foreach ($checkPermissions as $p) {
            if (isset($checkedPermissions[$p])) {
                $result[$p] = $checkedPermissions[$p];
                continue;
            }

            $parts = explode(':', $p);

            if ($parts[0] == 'plugin' && count($parts) == 4) {
                $isPlugin = true;
                array_shift($parts);
            } else {
                $isPlugin = false;
            }

            if (count($parts) != 3) {
                $result[$p] = false;
            } else {
                //check against bundle permissions class
                $permissionObject = $this->getPermissionObject($parts[0], false, $isPlugin);
                $result[$p]       = $permissionObject && $permissionObject->isSupported($parts[1], $parts[2]);
            }
        }

        return (is_array($permission)) ? $result : $result[$permission];
    }

    /**
     * Checks if the user has access to the requested entity
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
     * Retrieves all permissions
     *
     * @param boolean $forJs
     *
     * @return array
     */
    public function getAllPermissions($forJs = false)
    {
        $permissionObjects = $this->getPermissionObjects();
        $permissions       = array();
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

    /**
     * @return bool
     */
    public function isAnonymous()
    {
        $userEntity = $this->userHelper->getUser();
        return ($userEntity instanceof User && $userEntity->getId()) ? false : true;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return EntityManager
     */
    protected function getEm()
    {
        return $this->em;
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
     * @deprecated 1.2.3; to be removed in 2.0
     *
     * @return TokenStorageInterface
     */
    protected function getSecurityContext()
    {
        return $this->getTokenStorage();
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->tokenStorage;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }
}
