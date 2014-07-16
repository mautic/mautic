<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\CommonPermissions;

/**
 * Class LeadPermissions
 *
 * @package Mautic\LeadBundle\Security\Permissions
 */
class LeadPermissions extends CommonPermissions
{

    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'leads' => array(
                'viewown'     => 1,
                'viewother'   => 2,
                'editown'     => 4,
                'editother'   => 8,
                'create'      => 16,
                'deleteown'   => 32,
                'deleteother' => 64,
                'full'        => 1024
            ),
            'lists' => array(
                'viewother'   => 2,
                'editother'   => 8,
                'deleteother' => 64,
                'full'        => 1024
            ),
            'fields' => array(
                'full'        => 1024
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName ()
    {
        return 'lead';
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
        $builder->add('lead:leads', 'choice', array(
            'choices'  => array(
                'viewown'     => 'mautic.core.permissions.viewown',
                'viewother'   => 'mautic.core.permissions.viewother',
                'editown'     => 'mautic.core.permissions.editown',
                'editother'   => 'mautic.core.permissions.editother',
                'create'      => 'mautic.core.permissions.create',
                'deleteown'   => 'mautic.core.permissions.deleteown',
                'deleteother' => 'mautic.core.permissions.deleteother',
                'full'        => 'mautic.core.permissions.full'
            ),
            'label'    => 'mautic.lead.permissions.leads',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'lead\')'
            ),
            'data'     => (!empty($data['leads']) ? $data['leads'] : array())
        ));

        $builder->add('lead:lists', 'choice', array(
            'choices'  => array(
                'viewother'    => 'mautic.core.permissions.viewother',
                'editother'    => 'mautic.core.permissions.editother',
                'deleteother'  => 'mautic.core.permissions.deleteother',
                'full'         => 'mautic.core.permissions.full'
            ),
            'label'    => 'mautic.lead.permissions.lists',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'lead\')'
            ),
            'data'     => (!empty($data['lists']) ? $data['lists'] : array())
        ));

        $builder->add('lead:fields', 'choice', array(
            'choices'  => array(
                'full' => 'mautic.lead.field.permissions.full'
            ),
            'label'    => 'mautic.lead.permissions.fields',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'lead\')'
            ),
            'data'     => (!empty($data['fields']) ? $data['fields'] : array())
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        $leadPermissions = (isset($permissions['lead:leads'])) ? $permissions['lead:leads'] : array();

        //make sure the user has access to own leads as well if they have access to lists or fields
        if ((array_key_exists("lead:lists", $permissions) || array_key_exists("lead:fields", $permissions)) &&
            (!in_array("full", $leadPermissions) && !in_array("viewown", $leadPermissions) &&
                !in_array("viewother", $leadPermissions))) {
                $permissions['lead:leads'][] = 'viewown';
        }
    }
}