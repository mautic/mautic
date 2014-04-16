<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;

/**
 * Class UserPermissions
 *
 * @package Mautic\UserBundle\Security\Permissions
 */
class CommonPermissions {

    protected  $permissions = array();
    protected  $em;
    protected  $container;

    public function __construct(Container $container, EntityManager $em) {
        $this->container = $container;
        $this->em        = $em;
    }

    /**
     * Returns bundle's permissions array
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Checks to see if the requested permission is supported by the bundle
     *
     * @param $name
     * @param $perm
     * @return bool
     */
    public function isSupported($name, $level = '')
    {
        if (empty($level)) {
            //verify permission name only
            return isset($this->permissions[$name]);
        } else {
            //verify permission name and level as well
            return isset($this->permissions[$name][$level]);
        }

    }

    /**
     * Allows permission classes to be disabled if criteria is not met (such as bundle is disabled)
     *
     * @return bool
     */
    public function isEnabled() {
        return true;
    }

    /**
     * Returns the value assigned to a specific permission
     *
     * @param $name
     * @param $perm
     */
    public function getValue($name, $perm)
    {
        return ($this->isSupported($name, $perm)) ? $this->permissions[$name][$perm] : 0;
    }

    /**
     * Builds the bundle's specific form elements for its permissions
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options)
    {

    }

    /**
     * Returns the name of the permission set (should be the bundle identifier)
     *
     * @return string|void
     */
    public function getName()
    {
        return 'undefined';
    }

    /**
     * Takes an array from PermissionRepository::getPermissionsByRole() and converts the bitwise integers to an array
     * of permission names that can be used in forms, for example.
     *
     * @param array $perms
     * @return mixed
     */
    public function convertBitsToPermissionNames(array $permissions) {
        static $permissionLevels = array();
        $bundle = $this->getName();

        if (!in_array($bundle, $permissionLevels)) {
            $permissionLevels[$bundle] = array();
            if (isset($permissions[$bundle])) {
                if ($this->isEnabled()) {
                    foreach ($permissions[$bundle] as $permId => $details) {
                        $permName    = $details['name'];
                        $permBitwise = $details['bitwise'];
                        //ensure the permission still exists
                        if ($this->isSupported($permName)) {
                            $levels = $this->permissions[$permName];
                            //ensure that at least keys exist
                            $permissionLevels[$bundle][$permName]                      = array();
                            $permissionLevels[$bundle][$permName]["$bundle:$permName"] = $permId;
                            foreach ($levels as $levelName => $levelBit) {
                                //compare bit against levels to see if it is a match
                                if ($levelBit & $permBitwise) {
                                    //bitwise compares so add the level
                                    $permissionLevels[$bundle][$permName][] = $levelName;
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $permissionLevels[$bundle];
    }

    public function isGranted($userPermissions, $name, $level) {
        if (!$this->isSupported($name, $level)) {
            throw new DummyException("Permission " . $this->getName() . ":$name:$level was not found.");
        }

        if (!isset($userPermission[$name])) {
            //the user doesn't have implicit access
            return 0;
        }

        //test to see if the user has full permissions first
        if ($this->permissions[$name]['full'] & $userPermissions[$name]) {
            return 1;
        } else {
            //otherwise test for specific level
            return ($this->permissions[$name][$level] & $userPermissions[$name]);
        }
    }
}