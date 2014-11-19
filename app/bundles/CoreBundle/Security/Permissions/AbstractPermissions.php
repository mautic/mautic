<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Mautic\CategoryBundle\Helper\PermissionHelper;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AbstractPermissions
 */
abstract class AbstractPermissions
{

    /**
     * @var array
     */
    protected $permissions = array();

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
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
     * @param string $name
     * @param string $level
     *
     * @return bool
     */
    public function isSupported($name, $level = '')
    {
        list($name, $level) = $this->getSynonym($name, $level);

        if (empty($level)) {
            //verify permission name only
            return isset($this->permissions[$name]);
        }

        //verify permission name and level as well
        return isset($this->permissions[$name][$level]);
    }

    /**
     * Allows permission classes to be disabled if criteria is not met (such as bundle is disabled)
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Returns the value assigned to a specific permission
     *
     * @param string $name
     * @param string $perm
     *
     * @return int
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
     * @param array                $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
    }

    /**
     * Returns the name of the permission set (should be the bundle identifier)
     *
     * @return string|void
     */
    abstract public function getName();

    /**
     * Takes an array from PermissionRepository::getPermissionsByRole() and converts the bitwise integers to an array
     * of permission names that can be used in forms, for example.
     *
     * @param array $permissions
     *
     * @return mixed
     */
    public function convertBitsToPermissionNames(array $permissions)
    {
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
                            $permissionLevels[$bundle][$permName] = array();
                            //$permissionLevels[$bundle][$permName]["$bundle:$permName"] = $permId;
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

    /**
     * Allows the bundle permission class to utilize synonyms for permissions
     *
     * @param string $name
     * @param string $level
     *
     * @return array
     */
    protected function getSynonym($name, $level)
    {
        if (in_array($level, array('viewown', 'viewother'))) {
            if (isset($this->permissions[$name]['view'])) {
                $level = 'view';
            }
        } elseif (in_array($level, array('editown', 'editother'))) {
            if (isset($this->permissions[$name]['edit'])) {
                $level = 'edit';
            }
        } elseif (in_array($level, array('deleteown', 'deleteother'))) {
            if (isset($this->permissions[$name]['delete'])) {
                $level = 'delete';
            }
        } elseif (in_array($level, array('publishown', 'publishother'))) {
            if (isset($this->permissions[$name]['publish'])) {
                $level = 'publish';
            }
        }

        return array($name, $level);
    }

    /**
     * Determines if the user has access to the specified permission
     *
     * @param array  $userPermissions
     * @param string $name
     * @param string $level
     *
     * @return boolean
     */
    public function isGranted($userPermissions, $name, $level)
    {
        list($name, $level) = $this->getSynonym($name, $level);

        if (!isset($userPermissions[$name])) {
            //the user doesn't have implicit access
            return false;
        } elseif ($this->permissions[$name]['full'] & $userPermissions[$name]) {
            return true;
        } else {
            //otherwise test for specific level
            $result = ($this->permissions[$name][$level] & $userPermissions[$name]);

            return ($result) ? true : false;
        }
    }

    /**
     * Gives the bundle the opportunity to force certain permissions if another is selected
     *
     * @param array $permissions
     *
     * @return void
     */
    public function analyzePermissions(array &$permissions)
    {
        foreach ($permissions as $permissionGroup => &$perms) {
            list($bundle, $level) = explode(':', $permissionGroup);

            if ($bundle != $this->getName()) {
                continue;
            }

            $updatedPerms = $perms;
            foreach ($perms as $perm) {
                $required = array();
                switch ($perm) {
                    case 'editother':
                    case 'edit':
                        $required = array('viewother', 'viewown');
                        break;
                    case 'deleteother':
                    case 'delete':
                        $required = array('editother', 'viewother', 'viewown');
                        break;
                    case 'publishother':
                    case 'publish':
                        $required = array('viewother', 'viewown');
                        break;
                    case 'viewother':
                    case 'editown':
                    case 'deleteown':
                    case 'publishown':
                    case 'create':
                        $required = array('viewown');
                        break;
                }
                if (!empty($required)) {
                    foreach ($required as $r) {
                        list($ignore, $r) = $this->getSynonym($level, $r);
                        if ($this->isSupported($level, $r) && !in_array($r, $updatedPerms)) {
                            $updatedPerms[] = $r;
                        }
                    }
                }
            }

            $perms = $updatedPerms;
        }
    }

    /**
     * Generates an array of granted and total permissions
     *
     * @param array $data
     *
     * @return array
     */
    public function getPermissionRatio(array $data)
    {
        $totalAvailable = $totalGranted = 0;

        foreach ($this->permissions as $level => $perms) {
            $perms = array_keys($perms);
            $totalAvailable += count($perms);

            if (in_array('full', $perms)) {
                if (count($perms) === 1) {
                    //full is the only permission so count as 1
                    if (!empty($data[$level]) && in_array('full', $data[$level])) {
                        $totalGranted++;
                    }
                } else {
                    //remove full from total count
                    $totalAvailable--;
                    if (!empty($data[$level]) && in_array('full', $data[$level])) {
                        //user has full access so sum perms minus full
                        $totalGranted += count($perms) - 1;
                        //move on to the next level
                        continue;
                    }
                }
            }

            if (isset($data[$level])) {
                $totalGranted += count($data[$level]);
            }
        }

        return array($totalGranted, $totalAvailable);
    }

    /**
     * Gives the bundle an opportunity to change how JavaScript calculates permissions granted
     *
     * @param array $perms
     *
     * @return void
     */
    public function parseForJavascript(array &$perms)
    {
    }

    /**
     * Adds the standard permission set of view, edit, create, delete, publish and full
     *
     * @param array $permissionNames
     * @param bool  $includePublish
     *
     * @return void
     */
    protected function addStandardPermissions($permissionNames, $includePublish = true)
    {
        if (!is_array($permissionNames)) {
            $permissionNames = array($permissionNames);
        }

        foreach ($permissionNames as $p) {
            $this->permissions[$p] = array(
                'view'    => 4,
                'edit'    => 16,
                'create'  => 32,
                'delete'  => 128,
                'full'    => 1024
            );
            if ($includePublish) {
                $this->permissions[$p]['publish'] = 512;
            }
        }
    }

    /**
     * Adds the standard permission set of view, edit, create, delete, publish and full to the form builder
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param array                $data
     * @param bool                 $includePublish
     */
    protected function addStandardFormFields($bundle, $level, &$builder, $data, $includePublish = true)
    {
        $choices = array(
            'view'   => 'mautic.core.permissions.view',
            'edit'   => 'mautic.core.permissions.edit',
            'create' => 'mautic.core.permissions.create',
            'delete' => 'mautic.core.permissions.delete',
            'full'   => 'mautic.core.permissions.full'
        );

        if ($includePublish) {
            $choices['publish'] = 'mautic.core.permissions.publish';
        }

        $label = ($level == "categories") ? "mautic.category.permissions.categories" : "mautic.$bundle.permissions.$level";
        $builder->add("$bundle:$level", 'button_group', array(
            'choices'  => $choices,
            'label'    => $label,
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \''.$bundle.'\')'
            ),
            'data'     => (!empty($data[$level]) ? $data[$level] : array())
        ));
    }


    /**
     * Adds the standard permission set of viewown, viewother, editown, editother, create, deleteown, deleteother,
     * publishown, publishother and full
     *
     * @param array $permissionNames
     * @param bool  $includePublish
     */
    protected function addExtendedPermissions($permissionNames, $includePublish = true)
    {
        if (!is_array($permissionNames)) {
            $permissionNames = array($permissionNames);
        }

        foreach ($permissionNames as $p) {
            $this->permissions[$p] = array(
                'viewown'      => 2,
                'viewother'    => 4,
                'editown'      => 8,
                'editother'    => 16,
                'create'       => 32,
                'deleteown'    => 64,
                'deleteother'  => 128,
                'full'         => 1024
            );
            if ($includePublish) {
                $this->permissions[$p]['publishown']   = 256;
                $this->permissions[$p]['publishother'] = 512;
            }
        }
    }

    /**
     * Adds the standard permission set of viewown, viewother, editown, editother, create, deleteown, deleteother,
     * publishown, publishother and full to the form builder
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param array                $data
     * @param bool                 $includePublish
     */
    protected function addExtendedFormFields($bundle, $level, &$builder, $data, $includePublish = true)
    {
        $choices =  $includePublish ?
            array(
                'viewown'      => 'mautic.core.permissions.viewown',
                'viewother'    => 'mautic.core.permissions.viewother',
                'editown'      => 'mautic.core.permissions.editown',
                'editother'    => 'mautic.core.permissions.editother',
                'create'       => 'mautic.core.permissions.create',
                'deleteown'    => 'mautic.core.permissions.deleteown',
                'deleteother'  => 'mautic.core.permissions.deleteother',
                'publishown'   => 'mautic.core.permissions.publishown',
                'publishother' => 'mautic.core.permissions.publishother',
                'full'         => 'mautic.core.permissions.full'
            ) :
            array(
                'viewown'      => 'mautic.core.permissions.viewown',
                'viewother'    => 'mautic.core.permissions.viewother',
                'editown'      => 'mautic.core.permissions.editown',
                'editother'    => 'mautic.core.permissions.editother',
                'create'       => 'mautic.core.permissions.create',
                'deleteown'    => 'mautic.core.permissions.deleteown',
                'deleteother'  => 'mautic.core.permissions.deleteother',
                'full'         => 'mautic.core.permissions.full'
            );

        $builder->add("$bundle:$level", 'button_group', array(
                'choices'  => $choices,
                'label'    => "mautic.$bundle.permissions.$level",
                'expanded' => true,
                'multiple' => true,
                'attr'     => array(
                    'onclick' => 'Mautic.onPermissionChange(this, event, \''.$bundle.'\')'
                ),
                'data'     => (!empty($data[$level]) ? $data[$level] : array())
            )
        );
    }
}
