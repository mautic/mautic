<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AbstractPermissions.
 */
abstract class AbstractPermissions
{
    /**
     * @var array
     */
    protected $permissions = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Returns bundle's permissions array.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Checks to see if the requested permission is supported by the bundle.
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
     * Allows permission classes to be disabled if criteria is not met (such as bundle is disabled).
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Returns the value assigned to a specific permission.
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
     * Builds the bundle's specific form elements for its permissions.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
    }

    /**
     * Returns the name of the permission set (should be the bundle identifier).
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
        static $permissionLevels = [];
        $bundle                  = $this->getName();

        if (!in_array($bundle, $permissionLevels)) {
            $permissionLevels[$bundle] = [];
            if (isset($permissions[$bundle])) {
                if ($this->isEnabled()) {
                    foreach ($permissions[$bundle] as $permId => $details) {
                        $permName    = $details['name'];
                        $permBitwise = $details['bitwise'];
                        //ensure the permission still exists
                        if ($this->isSupported($permName)) {
                            $levels = $this->permissions[$permName];
                            //ensure that at least keys exist
                            $permissionLevels[$bundle][$permName] = [];
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
     * Allows the bundle permission class to utilize synonyms for permissions.
     *
     * @param string $name
     * @param string $level
     *
     * @return array
     */
    protected function getSynonym($name, $level)
    {
        if (in_array($level, ['viewown', 'viewother'])) {
            if (isset($this->permissions[$name]['view'])) {
                $level = 'view';
            }
        } elseif ($level == 'view') {
            if (isset($this->permissions[$name]['viewown'])) {
                $level = 'viewown';
            }
        } elseif (in_array($level, ['editown', 'editother'])) {
            if (isset($this->permissions[$name]['edit'])) {
                $level = 'edit';
            }
        } elseif ($level == 'edit') {
            if (isset($this->permissions[$name]['editown'])) {
                $level = 'editown';
            }
        } elseif (in_array($level, ['deleteown', 'deleteother'])) {
            if (isset($this->permissions[$name]['delete'])) {
                $level = 'delete';
            }
        } elseif ($level == 'delete') {
            if (isset($this->permissions[$name]['deleteown'])) {
                $level = 'deleteown';
            }
        } elseif (in_array($level, ['publishown', 'publishother'])) {
            if (isset($this->permissions[$name]['publish'])) {
                $level = 'publish';
            }
        } elseif ($level == 'publish') {
            if (isset($this->permissions[$name]['publishown'])) {
                $level = 'publishown';
            }
        }

        return [$name, $level];
    }

    /**
     * Determines if the user has access to the specified permission.
     *
     * @param array  $userPermissions
     * @param string $name
     * @param string $level
     *
     * @return bool
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
     * @param array $permissions
     * @param       $allPermissions
     * @param bool  $isSecondRound
     *
     * @return bool Return true if a second round is required after all other bundles have analyzed it's permissions
     */
    public function analyzePermissions(array &$permissions, $allPermissions, $isSecondRound = false)
    {
        $hasViewAccess = false;
        foreach ($permissions as $level => &$perms) {
            foreach ($perms as $perm) {
                $required = [];
                switch ($perm) {
                    case 'editother':
                    case 'edit':
                        $required = ['viewother', 'viewown'];
                        break;
                    case 'deleteother':
                    case 'delete':
                        $required = ['editother', 'viewother', 'viewown'];
                        break;
                    case 'publishother':
                    case 'publish':
                        $required = ['viewother', 'viewown'];
                        break;
                    case 'viewother':
                    case 'editown':
                    case 'deleteown':
                    case 'publishown':
                    case 'create':
                        $required = ['viewown'];
                        break;
                }
                if (!empty($required)) {
                    foreach ($required as $r) {
                        list($ignore, $r) = $this->getSynonym($level, $r);
                        if ($this->isSupported($level, $r) && !in_array($r, $perms)) {
                            $perms[] = $r;
                        }
                    }
                }
            }
            $hasViewAccess = (!$hasViewAccess && (in_array('view', $perms) || in_array('viewown', $perms)));
        }

        //check categories for view permissions and add it if the user has view access to the other permissions
        if (isset($this->permissions['categories']) && $hasViewAccess && (!isset($permissions['categories']) || !in_array('view', $permissions['categories']))) {
            $permissions['categories'][] = 'view';
        }

        return false;
    }

    /**
     * Generates an array of granted and total permissions.
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
                        ++$totalGranted;
                    }
                } else {
                    //remove full from total count
                    --$totalAvailable;
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

        return [$totalGranted, $totalAvailable];
    }

    /**
     * Gives the bundle an opportunity to change how JavaScript calculates permissions granted.
     *
     * @param array $perms
     */
    public function parseForJavascript(array &$perms)
    {
    }

    /**
     * Adds the standard permission set of view, edit, create, delete, publish and full.
     *
     * @param array $permissionNames
     * @param bool  $includePublish
     */
    protected function addStandardPermissions($permissionNames, $includePublish = true)
    {
        if (!is_array($permissionNames)) {
            $permissionNames = [$permissionNames];
        }

        foreach ($permissionNames as $p) {
            $this->permissions[$p] = [
                'view'   => 4,
                'edit'   => 16,
                'create' => 32,
                'delete' => 128,
                'full'   => 1024,
            ];
            if ($includePublish) {
                $this->permissions[$p]['publish'] = 512;
            }
        }
    }

    /**
     * Adds the standard permission set of view, edit, create, delete, publish and full to the form builder.
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param array                $data
     * @param bool                 $includePublish
     */
    protected function addStandardFormFields($bundle, $level, &$builder, $data, $includePublish = true)
    {
        $choices = [
            'view'   => 'mautic.core.permissions.view',
            'edit'   => 'mautic.core.permissions.edit',
            'create' => 'mautic.core.permissions.create',
            'delete' => 'mautic.core.permissions.delete',
        ];

        if ($includePublish) {
            $choices['publish'] = 'mautic.core.permissions.publish';
        }

        $choices['full'] = 'mautic.core.permissions.full';

        $label = ($level == 'categories') ? 'mautic.category.permissions.categories' : "mautic.$bundle.permissions.$level";
        $builder->add("$bundle:$level", 'permissionlist', [
            'choices' => $choices,
            'label'   => $label,
            'bundle'  => $bundle,
            'level'   => $level,
            'data'    => (!empty($data[$level]) ? $data[$level] : []),
        ]);
    }

    /**
     * Add a single full permission.
     *
     * @param array $permissionNames
     */
    protected function addManagePermission($permissionNames)
    {
        if (!is_array($permissionNames)) {
            $permissionNames = [$permissionNames];
        }

        foreach ($permissionNames as $p) {
            $this->permissions[$p] = [
                'manage' => 1024,
            ];
        }
    }

    /**
     * Adds a single full permission to the form builder, i.e. config only bundles.
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param array                $data
     */
    protected function addManageFormFields($bundle, $level, &$builder, $data)
    {
        $choices = [
            'manage' => 'mautic.core.permissions.manage',
        ];

        $builder->add("$bundle:$level", 'permissionlist', [
            'choices' => $choices,
            'label'   => "mautic.$bundle.permissions.$level",
            'data'    => (!empty($data[$level]) ? $data[$level] : []),
            'bundle'  => $bundle,
            'level'   => $level,
        ]);
    }

    /**
     * Adds the standard permission set of viewown, viewother, editown, editother, create, deleteown, deleteother,
     * publishown, publishother and full.
     *
     * @param array $permissionNames
     * @param bool  $includePublish
     */
    protected function addExtendedPermissions($permissionNames, $includePublish = true)
    {
        if (!is_array($permissionNames)) {
            $permissionNames = [$permissionNames];
        }

        foreach ($permissionNames as $p) {
            $this->permissions[$p] = [
                'viewown'     => 2,
                'viewother'   => 4,
                'editown'     => 8,
                'editother'   => 16,
                'create'      => 32,
                'deleteown'   => 64,
                'deleteother' => 128,
                'full'        => 1024,
            ];
            if ($includePublish) {
                $this->permissions[$p]['publishown']   = 256;
                $this->permissions[$p]['publishother'] = 512;
            }
        }
    }

    /**
     * Adds the standard permission set of viewown, viewother, editown, editother, create, deleteown, deleteother,
     * publishown, publishother and full to the form builder.
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param array                $data
     * @param bool                 $includePublish
     */
    protected function addExtendedFormFields($bundle, $level, &$builder, $data, $includePublish = true)
    {
        $choices = $includePublish ?
            [
                'viewown'      => 'mautic.core.permissions.viewown',
                'viewother'    => 'mautic.core.permissions.viewother',
                'editown'      => 'mautic.core.permissions.editown',
                'editother'    => 'mautic.core.permissions.editother',
                'create'       => 'mautic.core.permissions.create',
                'deleteown'    => 'mautic.core.permissions.deleteown',
                'deleteother'  => 'mautic.core.permissions.deleteother',
                'publishown'   => 'mautic.core.permissions.publishown',
                'publishother' => 'mautic.core.permissions.publishother',
                'full'         => 'mautic.core.permissions.full',
            ] :
            [
                'viewown'     => 'mautic.core.permissions.viewown',
                'viewother'   => 'mautic.core.permissions.viewother',
                'editown'     => 'mautic.core.permissions.editown',
                'editother'   => 'mautic.core.permissions.editother',
                'create'      => 'mautic.core.permissions.create',
                'deleteown'   => 'mautic.core.permissions.deleteown',
                'deleteother' => 'mautic.core.permissions.deleteother',
                'full'        => 'mautic.core.permissions.full',
            ];

        $builder->add("$bundle:$level", 'permissionlist', [
                'choices' => $choices,
                'label'   => "mautic.$bundle.permissions.$level",
                'data'    => (!empty($data[$level]) ? $data[$level] : []),
                'bundle'  => $bundle,
                'level'   => $level,
            ]
        );
    }
}
