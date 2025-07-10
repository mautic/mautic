<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Exception\PermissionBadFormatException;
use Mautic\CoreBundle\Security\Exception\PermissionNotFoundException;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CorePermissions implements ResetInterface
{
    private array $permissionClasses = [];

    private array $permissionObjectsByClass = [];

    private array $permissionObjectsByName = [];

    private array $grantedPermissions = [];

    private array $checkedPermissions = [];

    private bool $permissionObjectsGenerated = false;

    public function __construct(
        protected UserHelper $userHelper,
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
        private array $bundles,
        private array $pluginBundles,
    ) {
        $this->registerPermissionClasses();
    }

    public function reset(): void
    {
        $this->permissionObjectsGenerated = false;
    }

    public function setPermissionObject(AbstractPermissions $permissionObject): void
    {
        $this->permissionObjectsByClass[$permissionObject::class]     = $permissionObject;
        $this->permissionObjectsByName[$permissionObject->getName()]  = $permissionObject;
    }

    /**
     * Retrieves all permission objects.
     *
     * @return array
     */
    public function getPermissionObjects()
    {
        if ($this->permissionObjectsGenerated) {
            return $this->permissionObjectsByName;
        }

        foreach ($this->getPermissionClasses() as $class) {
            try {
                $this->getPermissionObject($class);
            } catch (\InvalidArgumentException) {
            }
        }

        $this->permissionObjectsGenerated = true;

        return $this->permissionObjectsByName;
    }

    /**
     * Returns the permission class object and sets it to global array.
     *
     * @param string $bundle         can be either short bundle name or full path to the permissions class
     * @param bool   $throwException
     *
     * @return AbstractPermissions
     *
     * @throws \InvalidArgumentException
     */
    public function getPermissionObject($bundle, $throwException = true)
    {
        if (empty($bundle)) {
            throw new \InvalidArgumentException("Bundle and permission type must be specified. {$bundle} given.");
        }

        try {
            $permissionObject = $this->findPermissionObject($bundle);
        } catch (\UnexpectedValueException $e) {
            try {
                $permissionObject = $this->instantiatePermissionObject($bundle); // @phpstan-ignore method.deprecated
                $this->setPermissionObject($permissionObject);
            } catch (\InvalidArgumentException $e) {
                if ($throwException) {
                    throw $e;
                }

                return false;
            }
        }

        if ($permissionObject->isEnabled()) {
            $permissionObject->definePermissions();
        }

        return $permissionObject;
    }

    /**
     * Generates the bit value for the bundle's permission.
     *
     * @throws \InvalidArgumentException
     */
    public function generatePermissions(array $permissions): array
    {
        $entities = [];

        // give bundles an opportunity to analyze and adjust permissions based on others
        $objects = $this->getPermissionObjects();

        // bust out permissions into their respective bundles
        $bundlePermissions = [];
        foreach ($permissions as $permission => $perms) {
            [$bundle, $level]                   = explode(':', $permission);
            $bundlePermissions[$bundle][$level] = $perms;
        }

        $bundles = array_keys($objects);

        foreach ($bundles as $bundle) {
            if (!isset($bundlePermissions[$bundle])) {
                $bundlePermissions[$bundle] = [];
            }
        }

        // do a first round to give bundles a chance to update everything and give an opportunity to require a second round
        // if the permission it is looking for from another bundle is not configured yet
        $secondRound = [];
        foreach ($objects as $bundle => $object) {
            $needsRoundTwo = $object->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions);
            if ($needsRoundTwo) {
                $secondRound[] = $bundle;
            }
        }

        foreach ($secondRound as $bundle) {
            $objects[$bundle]->analyzePermissions($bundlePermissions[$bundle], $bundlePermissions, true);
        }

        // create entities
        foreach ($bundlePermissions as $bundle => $permissions) {
            foreach ($permissions as $name => $perms) {
                $entity = new Permission();
                $entity->setBundle($bundle);
                $entity->setName($name);

                $bit    = 0;
                $object = $this->getPermissionObject($bundle);

                foreach ($perms as $perm) {
                    // get the bit for the perm
                    if (!$object->isSupported($name, $perm)) {
                        throw new \InvalidArgumentException("$perm does not exist for $bundle:$name");
                    }

                    $bit += $object->getValue($name, $perm);
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
     * @param string[]|string $requestedPermission
     * @param string          $mode                MATCH_ALL|MATCH_ONE|RETURN_ARRAY
     * @param User            $userEntity
     * @param bool            $allowUnknown        If the permission is not recognized, false will be returned.  Otherwise an
     *                                             exception will be thrown
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function isGranted($requestedPermission, $mode = 'MATCH_ALL', $userEntity = null, $allowUnknown = false)
    {
        // Initialize all permission classes if
        $this->getPermissionObjects();

        if (null === $userEntity) {
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
            if (false === in_array(count($parts), [3, 4])) {
                throw new PermissionBadFormatException($this->getTranslator()->trans('mautic.core.permissions.badformat', ['%permission%' => $permission]));
            }

            if ($userEntity->isAdmin()) {
                // admin user has access to everything
                $permissions[$permission] = true;
            } else {
                $activePermissions = ($userEntity instanceof User) ? $userEntity->getActivePermissions() : [];

                // check against bundle permissions class
                $permissionObject = $this->getPermissionObject($parts[0]);

                // Is the permission supported?
                if (!$permissionObject->isSupported($parts[1], $parts[2])) {
                    if ($allowUnknown) {
                        $permissions[$permission] = false;
                    } else {
                        throw new PermissionNotFoundException($this->getTranslator()->trans('mautic.core.permissions.notfound', ['%permission%' => $permission]));
                    }
                } elseif ('anon.' == $userEntity) {
                    // anon user or session timeout
                    $permissions[$permission] = false;
                } elseif ($permissionObject instanceof VirtualPermissions) {
                    $permissions[$permission] = $permissionObject->isVirtuallyGranted($parts[1], $parts[2]);
                } elseif (!isset($activePermissions[$parts[0]])) {
                    // user does not have implicit access to bundle so deny
                    $permissions[$permission] = false;
                } else {
                    $permissions[$permission] = $permissionObject->isGranted($activePermissions[$parts[0]], $parts[1], $parts[2]);
                }
            }

            $this->grantedPermissions[$permission] = $permissions[$permission];
        }

        if ('MATCH_ALL' == $mode) {
            // deny if any of the permissions are denied
            return in_array(0, $permissions) ? false : true;
        } elseif ('MATCH_ONE' == $mode) {
            // grant if any of the permissions were granted
            return in_array(1, $permissions) ? true : false;
        } elseif ('RETURN_ARRAY' == $mode) {
            return $permissions;
        } else {
            throw new PermissionNotFoundException($this->getTranslator()->trans('mautic.core.permissions.mode.notfound', ['%mode%' => $mode]));
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
        // Generate all permission objects in case they haven't been already.
        $this->getPermissionObjects();

        $checkPermissions = (!is_array($permission)) ? [$permission] : $permission;

        $result = [];
        foreach ($checkPermissions as $p) {
            if (isset($this->checkedPermissions[$p])) {
                $result[$p] = $this->checkedPermissions[$p];
                continue;
            }

            $parts = explode(':', $p);
            if (3 != count($parts)) {
                $result[$p] = false;
            } else {
                // check against bundle permissions class
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
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId = 0): bool
    {
        $user = $this->userHelper->getUser();
        if (!is_object($user)) {
            // user is likely anon. so assume no access and let controller handle via published status
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

        if (0 === $ownerId) {
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
     */
    public function getAllPermissions($forJs = false): array
    {
        $permissionObjects = $this->getPermissionObjects();
        $permissions       = [];
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

    public function isAnonymous(): bool
    {
        $userEntity = $this->userHelper->getUser();

        return ($userEntity instanceof User && !$userEntity->isGuest()) ? false : true;
    }

    /**
     * @return TranslatorInterface
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

    protected function getParams(): array
    {
        return $this->coreParametersHelper->all();
    }

    protected function getPermissionClasses(): array
    {
        if (empty($this->permissionClasses)) {
            $this->registerPermissionClasses();
        }

        return $this->permissionClasses;
    }

    /**
     * @deprecated To be removed in 4.0.
     *
     * It is recommended to define permission objects via DI with tag 'mautic.permissions'.
     * This is fallback for keeping BC where the permission object is instantiated on the fly.
     *
     * @throws \InvalidArgumentException
     */
    private function instantiatePermissionObject(string $class): AbstractPermissions
    {
        if (empty($this->getPermissionClasses()[$class])) {
            throw new \InvalidArgumentException("Permission class not found for {$class} in permissions classes");
        }

        $permissionClass = $this->getPermissionClasses()[$class];

        return new $permissionClass($this->getParams());
    }

    /**
     * Search for the permission objects by name or by class name.
     *
     * @throws \UnexpectedValueException
     */
    private function findPermissionObject(string $bundle): AbstractPermissions
    {
        if (isset($this->permissionObjectsByName[$bundle])) {
            return $this->permissionObjectsByName[$bundle];
        }

        if (isset($this->permissionObjectsByClass[$bundle])) {
            return $this->permissionObjectsByClass[$bundle];
        }

        throw new \UnexpectedValueException("There is no permission object for {$bundle}");
    }

    /**
     * Register permission classes.
     */
    private function registerPermissionClasses(): void
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
