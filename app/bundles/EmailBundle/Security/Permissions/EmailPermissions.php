<?php

namespace Mautic\EmailBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = [
            'campaigns' => [
                'sendtodnc' => 2,
            ],
        ];
        $this->addStandardPermissions(['categories', 'emails']);
    }

    public function getName(): string
    {
        return 'email';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('email', 'categories', $builder, $data);
        $this->addExtendedFormFields('email', 'emails', $builder, $data);

        $builder->add(
            'email:campaigns',
            PermissionListType::class,
            [
                'choices'           => [
                    'mautic.email.permissions.sendtodnc' => 'sendtodnc',
                ],
                'choices_as_values' => true,
                'label'             => 'mautic.email.permissions.campaigns',
                'data'              => (!empty($data['campaigns']) ? $data['campaigns'] : []),
                'bundle'            => 'email',
                'level'             => 'campaigns',
            ]
        );
    }
}
