<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class EmailPermissions
 *
 * @package Mautic\EmailBundle\Security\Permissions
 */
class EmailPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'categories' => array(
                'view'          => 1,
                'edit'          => 4,
                'create'        => 8,
                'delete'        => 32,
                'publish'       => 64,
                'full'          => 1024
            ),
            'emails' => array(
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
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'email';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $builder->add('email:categories', 'button_group', array(
            'choices'    => array(
                'view'    => 'mautic.core.permissions.view',
                'edit'    => 'mautic.core.permissions.edit',
                'create'  => 'mautic.core.permissions.create',
                'publish' => 'mautic.core.permissions.publish',
                'delete'  => 'mautic.core.permissions.delete',
                'full'    => 'mautic.core.permissions.full'
            ),
            'label'      => 'mautic.email.permissions.categories',
            'label_attr' => array('class' => 'control-label'),
            'expanded'   => true,
            'multiple'   => true,
            'attr'       => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'email\')'
            ),
            'data'      => (!empty($data['categories']) ? $data['categories'] : array())
        ));

        $builder->add('email:emails', 'button_group', array(
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
            'label'    => 'mautic.email.permissions.emails',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'email\')'
            ),
            'data'     => (!empty($data['emails']) ? $data['emails'] : array())
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
            //set some synonyms
            switch ($level) {
                case "viewown":
                case "viewother":
                    $level = "view";
                    break;
                case "editown":
                case "editother":
                    $level = "edit";
                    break;
                case "deleteown":
                case "deleteother":
                    $level = "delete";
                    break;
                case "publishown":
                case "publishother":
                    $level = "publish";
                    break;
            }
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

        $emailPermissions = (isset($permissions['email:emails'])) ? $permissions['email:emails'] : array();
        $catPermissions  = (isset($permissions['email:categories'])) ? $permissions['email:categories'] : array();
        //make sure the user has access to view categories if they have access to view emails
        if ((isset($emailPermissions['viewown']) || isset($emailPermissions['viewother']))
            && !isset($catPermissions['view'])) {
            $permissions['email:categories'][] = 'view';
        }
    }
}