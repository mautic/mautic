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
        $this->permissions = [
            'batch' => [
                'export' => 4,
                'full'   => 1024,
            ],
        ];
        parent::__construct($params);
        $this->addExtendedPermissions('reports');
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

        $builder->add('report:batch', 'permissionlist', [
            'choices' => [
                'export'   => 'mautic.core.permissions.export',
            ],
            'label'  => 'mautic.report.permissions.batch',
            'data'   => (!empty($data['batch']) ? $data['batch'] : []),
            'bundle' => 'form',
            'level'  => 'batch',
        ]);
    }
}
