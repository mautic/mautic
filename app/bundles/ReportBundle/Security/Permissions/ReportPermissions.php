<?php

namespace Mautic\ReportBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class ReportPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('reports');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'report';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addExtendedFormFields('report', 'reports', $builder, $data);
    }
}
