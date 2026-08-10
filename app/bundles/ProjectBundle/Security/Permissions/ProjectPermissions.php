<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProjectPermissions extends AbstractPermissions
{
    private const string PERMISSION_BASE = 'project:project';

    public const string CAN_VIEW         = self::PERMISSION_BASE.':view';

    public const string CAN_EDIT         = self::PERMISSION_BASE.':edit';

    public const string CAN_CREATE       = self::PERMISSION_BASE.':create';

    public const string CAN_DELETE       = self::PERMISSION_BASE.':delete';

    public const string CAN_ASSOCIATE    = self::PERMISSION_BASE.':associate';

    public function definePermissions(): void
    {
        $this->addStandardPermissions([$this->getName()], false);

        // Add the associate permission directly to the permissions array
        $this->permissions[$this->getName()]['associate'] = 8;
    }

    public function getName(): string
    {
        return 'project';
    }

    /**
     * @param mixed[] $options
     * @param mixed[] $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $builder->add(
            self::PERMISSION_BASE,
            PermissionListType::class,
            [
                'choices' => [
                    'mautic.core.permissions.view'      => 'view',
                    'mautic.core.permissions.associate' => 'associate',
                    'mautic.core.permissions.edit'      => 'edit',
                    'mautic.core.permissions.create'    => 'create',
                    'mautic.core.permissions.delete'    => 'delete',
                    'mautic.core.permissions.full'      => 'full',
                ],
                'label'   => 'mautic.project.permissions.project',
                'bundle'  => $this->getName(),
                'level'   => $this->getName(),
                'data'    => (!empty($data[$this->getName()]) ? $data[$this->getName()] : []),
            ]
        );
    }
}
