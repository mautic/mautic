<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Security\Permissions;

use Mautic\CategoryBundle\Helper\PermissionHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class FormPermissions
 *
 * @package Mautic\FormBundle\Security\Permissions
 */
class FormPermissions extends AbstractPermissions
{

    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'forms' => array(
                'viewown'      => 2,
                'viewother'    => 4,
                'editown'      => 8,
                'editother'    => 16,
                'create'       => 32,
                'deleteown'    => 64,
                'deleteother'  => 128,
                'publishown'   => 256,
                'publishother' => 512,
                'full'         => 1024
            )
        );

        PermissionHelper::addCategoryPermissions($this->permissions);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName ()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $data
     */
    public function buildForm (FormBuilderInterface &$builder, array $options, array $data)
    {
        PermissionHelper::buildForm('form', $builder, $data);

        $builder->add('form:forms', 'button_group', array(
            'choices'  => array(
                'viewown'      => 'mautic.core.permissions.viewown',
                'viewother'    => 'mautic.core.permissions.viewother',
                'editown'      => 'mautic.core.permissions.editown',
                'editother'    => 'mautic.core.permissions.editother',
                'create'       => 'mautic.core.permissions.create',
                'deleteown'    => 'mautic.core.permissions.deleteown',
                'deleteother'  => 'mautic.core.permissions.deleteother',
                'publishown'   => 'mautic.core.permissions.publishown',
                'publishother' => 'mautic.core.permissions.publishother',
                'full'         => 'mautic.core.permissions.full'
            ),
            'label'    => 'mautic.form.permissions.forms',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'form\')'
            ),
            'data'     => (!empty($data['forms']) ? $data['forms'] : array())
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param $name
     * @param $level
     * @return array
     */
    protected function getSynonym($name, $level) {
        if ($name == "categories") {
            $level = PermissionHelper::getSynonym($level);
        }

        return array($name, $level);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        //analyze category permissions
        PermissionHelper::analyzePermissions('form', 'forms', $permissions);
    }
}