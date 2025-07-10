<?php

namespace Mautic\ReportBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class ReportPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('reports');
        $this->addCustomPermission('export', ['enable' => 1024]);
    }

    public function getName(): string
    {
        return 'report';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addExtendedFormFields('report', 'reports', $builder, $data);
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
