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
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class LeadPermissions
 *
 * @package Mautic\LeadBundle\Security\Permissions
 */
class LeadPermissions extends AbstractPermissions
{

    public function __construct($params)
    {
        parent::__construct($params);

        $this->permissions = array(
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
        $this->addExtendedPermissions('leads', false);
        $this->addExtendedPermissions('notes', false);
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
        $this->addExtendedFormFields('lead', 'leads', $builder, $data, false);

        $builder->add('lead:lists', 'button_group', array(
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

        $builder->add('lead:fields', 'button_group', array(
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

        $this->addExtendedFormFields('lead', 'notes', $builder, $data, false);
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

        //make sure the user has access to own leads as well if they have access to lists, notes or fields
        if ((array_key_exists("lead:lists", $permissions) ||
            array_key_exists("lead:fields", $permissions) ||
            array_key_exists("lead:notes", $permissions)) &&
            (!in_array("full", $leadPermissions) && !in_array("viewown", $leadPermissions) &&
                !in_array("viewother", $leadPermissions))) {
                $permissions['lead:leads'][] = 'viewown';
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $name
     * @param $level
     * @return array
     */
    protected function getSynonym($name, $level) {
        if ($name == "fields") {
            //set some synonyms
            switch ($level) {
                case "publishown":
                case "publishother":
                    $level = "full";
                    break;
            }
        }

        return array($name, $level);
    }

}
