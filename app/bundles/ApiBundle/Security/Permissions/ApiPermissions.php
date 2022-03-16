<?php

namespace Mautic\ApiBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ApiPermissions.
 */
class ApiPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'api';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
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

    /**
     * {@inheritdoc}
     */
    public function getValue($name, $perm)
    {
        //ensure api is enabled system wide
        if (empty($this->params['api_enabled'])) {
            return 0;
        }

        return parent::getValue($name, $perm);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return !empty($this->params['api_enabled']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSynonym($name, $level)
    {
        if ('access' == $name && 'granted' == $level) {
            return [$name, 'full'];
        }

        return parent::getSynonym($name, $level);
    }
}
