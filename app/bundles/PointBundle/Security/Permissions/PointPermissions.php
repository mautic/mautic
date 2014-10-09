<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Security\Permissions;

use Mautic\CategoryBundle\Helper\PermissionHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class PointPermissions
 *
 * @package Mautic\PointBundle\Security\Permissions
 */
class PointPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('points');
        $this->addStandardPermissions('triggers');
        $this->addStandardPermissions('categories');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'point';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('point', 'categories', $builder, $data);
        $this->addStandardFormFields('point', 'points', $builder, $data);
        $this->addStandardFormFields('point', 'triggers', $builder, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        PermissionHelper::analyzePermissions('point', 'points', $permissions);
        PermissionHelper::analyzePermissions('point', 'triggers', $permissions);
    }
}
