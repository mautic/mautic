<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ReportPermissions.
 */
class ReportPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('reports');
        $this->addCustomPermission('export', ['disable' => 1024]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'report';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addExtendedFormFields('report', 'reports', $builder, $data);
        $this->addCustomFormFields(
            $this->getName(),
            'export',
            $builder,
            'mautic.core.permissions.export',
            ['mautic.core.permissions.disable' => 'disable'],
            $data
        );
    }
}
