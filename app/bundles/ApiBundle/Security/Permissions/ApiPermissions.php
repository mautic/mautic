<?php

namespace Mautic\ApiBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

class ApiPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->permissions = [
            'access' => [
                'full' => 1024,
            ],
        ];
        $this->addStandardPermissions('clients', false);
    }

    public function getName(): string
    {
        return 'api';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $builder->add(
            'api:access',
            PermissionListType::class,
            [
                'choices' => [
                    'mautic.api.permissions.granted' => 'full',
                ],
                'label'             => 'mautic.api.permissions.apiaccess',
                'data'              => (!empty($data['access']) ? $data['access'] : []),
                'bundle'            => 'api',
                'level'             => 'access',
            ]
        );

        $this->addStandardFormFields('api', 'clients', $builder, $data, false);
    }

    public function getValue($name, $perm)
    {
        // ensure api is enabled system wide
        if (empty($this->params['api_enabled'])) {
            return 0;
        }

        return parent::getValue($name, $perm);
    }

    public function isEnabled(): bool
    {
        return !empty($this->params['api_enabled']);
    }

    protected function getSynonym($name, $level)
    {
        if ('access' == $name && 'granted' == $level) {
            return [$name, 'full'];
        }

        return parent::getSynonym($name, $level);
    }
}
