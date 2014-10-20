<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class DahsboardPermissions
 *
 * @package Mautic\DashboardBundle\Security\Permissions
 */
class DashboardPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('dashboards');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'dastboard';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addExtendedFormFields('dashboard', 'dashboards', $builder, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        PermissionHelper::analyzePermissions('dashboard', 'dashboards', $permissions);
    }
}