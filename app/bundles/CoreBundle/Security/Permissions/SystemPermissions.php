<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

final class SystemPermissions extends AbstractPermissions
{
    /**
     * @param mixed[] $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('themes');
        $this->permissions['file_manager'] = ['full' => 1024];
    }

    public function getName(): string
    {
        return 'core';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('core', 'themes', $builder, $data);
        $this->addExtendedFormFields('core', 'file_manager', $builder, $data);
    }

    /**
     * @param array<mixed> $data
     */
    protected function addExtendedFormFields($bundle, $level, &$builder, $data, $includePublish = true): void
    {
        $choices = [
            'mautic.core.permissions.full' => 'full',
        ];

        $builder->add(
            "$bundle:$level",
            PermissionListType::class,
            [
                'choices'           => $choices,
                'choices_as_values' => true,
                'label'             => $this->getLabel($bundle, $level),
                'data'              => (!empty($data[$level]) ? $data[$level] : []),
                'bundle'            => $bundle,
                'level'             => $level,
            ]
        );
    }
}
