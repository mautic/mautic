<?php

namespace Mautic\FormBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class FormPermissions extends AbstractPermissions
{
    /**
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addCustomPermission('export', ['enable' => 1024]);
        $this->addExtendedPermissions('forms');
        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'form';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields($this->getName(), 'categories', $builder, $data);
        $this->addExtendedFormFields($this->getName(), 'forms', $builder, $data);
        $this->addCustomFormFields(
            $this->getName(),
            'export',
            $builder,
            'mautic.core.permissions.export',
            ['mautic.core.permissions.enable' => 'enable'],
            $data
        );
    }
}
