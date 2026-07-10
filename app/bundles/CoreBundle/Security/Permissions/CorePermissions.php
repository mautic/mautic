<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Exception\PermissionBadFormatException;
use Mautic\CoreBundle\Security\Exception\PermissionNotFoundException;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CorePermissions implements ResetInterface
{
    private array $permissionClasses = [];

    private array $permissionObjectsByClass = [];

    private array $permissionObjectsByName = [];

    private array $grantedPermissions = [];

    private array $checkedPermissions = [];

    /**
     * @var array<int, int[]>
     */
    private array $sameRoleUserIds = [];

    private bool $permissionObjectsGenerated = false;

    public function __construct(
        protected UserHelper $userHelper,
        private readonly TranslatorInterface $translator,
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly array $bundles,
        private readonly array $pluginBundles,
        private readonly UserRepository $userRepository,
    ) {
        $this->registerPermissionClasses();
    }

    public function reset(): void
    {
        $this->permissionObjectsGenerated = false;
        $this->sameRoleUserIds            = [];
    }

    public function setPermissionObject(AbstractPermissions $permissionObject): void
    {
        $this->permissionObjectsByClass[$permissionObject::class]     = $permissionObject;
        $this->permissionObjectsByName[$permissionObject->getName()]  = $permissionObject;
    }

    /**
     * Retrieves all permission objects.
     */
    public function getPermissionObjects(): array
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
     * @throws \InvalidArgumentException
     */
    public function getPermissionObject($bundle, $throwException = true): false|AbstractPermissions
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
                        throw new \InvalidArgumentException("{$perm} does not exist for {$bundle}:{$name}");
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
     * @return ($mode is 'RETURN_ARRAY' ? array<mixed> : bool)
     *
     * @throws \InvalidArgumentException
     */
    public function isGranted($requestedPermission, $mode = 'MATCH_ALL', $userEntity = null, $allowUnknown = false): bool|array
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
            return !in_array(0, $permissions);
        } elseif ('MATCH_ONE' == $mode) {
            // grant if any of the permissions were granted
            return in_array(1, $permissions);
        } elseif ('RETURN_ARRAY' == $mode) {
            return $permissions;
        }
        throw new PermissionNotFoundException($this->getTranslator()->trans('mautic.core.permissions.mode.notfound', ['%mode%' => $mode]));
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
            if (3 !== count($parts)) {
                $result[$p] = false;
            } else {
                // check against bundle permissions class
                $permissionObject = $this->getPermissionObject($parts[0], false);
                $result[$p]       = $permissionObject && $permissionObject->isSupported($parts[1], $parts[2]);
            }
        }

        return (is_array($permission)) ? $result : $result[$permission];
    }

    public function hasPublishAccessForEntity(FormEntity $entity, string $ownPermission, string $otherPermission): bool
    {
        $user = $this->userHelper->getUser();

        if (!$user) {
            return false;
        }

        $hasOwnPermission   = $this->isGranted($ownPermission);
        $hasOtherPermission = $this->isGranted($otherPermission);

        if (!$hasOwnPermission && !$hasOtherPermission) {
            return false;
        }

        if ($hasOwnPermission && $entity->isNew()) {
            return true;
        }

        $ownerId = method_exists($entity, 'getPermissionUser') ? (int) $entity->getPermissionUser() : (int) $entity->getCreatedBy();

        if ($hasOwnPermission && !$entity->isNew() && $ownerId === (int) $user->getId()) {
            return true;
        }

        return $hasOtherPermission && !$entity->isNew() && $ownerId !== (int) $user->getId();
    }

    /**
     * Checks if the user has access to the requested entity.
     *
     * @param string|bool      $ownPermission
     * @param string|bool      $otherPermission
     * @param User|int         $ownerId
     * @param string|bool|null $sameRolePermission
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId = 0, $sameRolePermission = null): bool
    {
        $user = $this->userHelper->getUser();
        if (!is_object($user)) {
            // user is likely anon. so assume no access and let controller handle via published status
            return false;
        }

        [$own, $other] = $this->getOwnerPermissions($ownPermission, $otherPermission);
        $sameRole      = $this->isSameRoleGranted($ownPermission, $otherPermission, $sameRolePermission);
        $ownerIdInt    = ($ownerId instanceof User) ? (int) $ownerId->getId() : (int) $ownerId;

        return $this->hasEntityAccessForOwner($user, $ownerId, $ownerIdInt, $own, $other, $sameRole);
    }

    /**
     * @param string|bool $ownPermission
     * @param string|bool $otherPermission
     *
     * @return array{0: bool, 1: bool}
     */
    private function getOwnerPermissions($ownPermission, $otherPermission): array
    {
        if (!is_bool($ownPermission) && !is_bool($otherPermission)) {
            $permissions = $this->isGranted(
                [$ownPermission, $otherPermission],
                'RETURN_ARRAY'
            );

            return [$permissions[$ownPermission], $permissions[$otherPermission]];
        }

        return [
            !is_bool($ownPermission) ? $this->isGranted($ownPermission) : $ownPermission,
            !is_bool($otherPermission) ? $this->isGranted($otherPermission) : $otherPermission,
        ];
    }

    /**
     * @param string|bool      $ownPermission
     * @param string|bool      $otherPermission
     * @param string|bool|null $sameRolePermission
     */
    private function isSameRoleGranted($ownPermission, $otherPermission, $sameRolePermission): bool
    {
        $sameRolePermission = $this->resolveSameRolePermission($ownPermission, $otherPermission, $sameRolePermission);

        if (is_bool($sameRolePermission)) {
            return $sameRolePermission;
        }

        return null !== $sameRolePermission
            && $this->checkPermissionExists($sameRolePermission)
            && $this->isGranted($sameRolePermission);
    }

    /**
     * @param string|bool      $ownPermission
     * @param string|bool      $otherPermission
     * @param string|bool|null $sameRolePermission
     *
     * @return string|bool|null
     */
    private function resolveSameRolePermission($ownPermission, $otherPermission, $sameRolePermission)
    {
        if (null !== $sameRolePermission) {
            return $sameRolePermission;
        }

        if (!is_bool($ownPermission)) {
            return $this->toSameRolePermission($ownPermission);
        }

        return !is_bool($otherPermission) ? $this->toSameRolePermission($otherPermission) : null;
    }

    /**
     * @param User|int $ownerParam
     */
    private function hasEntityAccessForOwner(User $currentUser, $ownerParam, int $ownerId, bool $own, bool $other, bool $sameRole): bool
    {
        if (0 === $ownerId) {
            // Owner unknown: only 'other' should allow access. Same-role needs an owner context.
            return (bool) $other;
        }

        if (($own && (int) $currentUser->getId() === $ownerId) || ($other && (int) $currentUser->getId() !== $ownerId)) {
            return true;
        }

        return $sameRole && $this->ownerHasSameRole($currentUser, $ownerParam, $ownerId);
    }

    /**
     * @param User|int $ownerParam
     */
    private function ownerHasSameRole(User $currentUser, $ownerParam, int $ownerId): bool
    {
        if ($ownerParam instanceof User) {
            return $ownerParam->getRole()
                && $currentUser->getRole()
                && $ownerParam->getRole()->getId() === $currentUser->getRole()->getId();
        }

        if (null === $currentUser->getRole()) {
            return false;
        }

        $roleId = (int) $currentUser->getRole()->getId();
        if (!array_key_exists($roleId, $this->sameRoleUserIds)) {
            $this->sameRoleUserIds[$roleId] = $this->userRepository->findUserIdsByRole($roleId);
        }

        return in_array($ownerId, $this->sameRoleUserIds[$roleId], true);
    }

    private function toSameRolePermission(string $permission): ?string
    {
        if (preg_match('/(view|edit|delete|publish)(own|other)$/', $permission, $matches)) {
            return preg_replace('/(view|edit|delete|publish)(own|other)$/', '$1samerole', $permission);
        }

        return null;
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

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return mixed[]
     */
    protected function getBundles(): array
    {
        return $this->bundles;
    }

    protected function getPluginBundles(): array
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
