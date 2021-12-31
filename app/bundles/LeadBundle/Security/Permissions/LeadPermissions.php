<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

class LeadPermissions extends AbstractPermissions
{
    public const LISTS_VIEW         = 'lead:lists:view';
    public const LISTS_VIEW_OWN     = 'lead:lists:viewown';
    public const LISTS_VIEW_OTHER   = 'lead:lists:viewother';
    public const LISTS_EDIT_OWN     = 'lead:lists:editown';
    public const LISTS_EDIT_OTHER   = 'lead:lists:editother';
    public const LISTS_CREATE       = 'lead:lists:create';
    public const LISTS_DELETE_OWN   = 'lead:lists:deleteown';
    public const LISTS_DELETE_OTHER = 'lead:lists:deleteother';
    public const LISTS_FULL         = 'lead:lists:full';

    public function __construct($params)
    {
        parent::__construct($params);

        $this->permissions = [
            'fields' => [
                'full' => 1024,
            ],
        ];

        $this->addExtendedPermissions('leads', false);
        $this->addExtendedPermissions('lists', false);
        $this->addStandardPermissions('imports');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addExtendedFormFields('lead', 'leads', $builder, $data, false);

        $this->addExtendedFormFields('lead', 'lists', $builder, $data, false);

        $builder->add(
            'lead:fields',
            PermissionListType::class,
            [
                'choices' => [
                    'mautic.core.permissions.manage' => 'full',
                ],
                'label'             => 'mautic.lead.permissions.fields',
                'data'              => (!empty($data['fields']) ? $data['fields'] : []),
                'bundle'            => 'lead',
                'level'             => 'fields',
            ]
        );

        $this->addStandardFormFields($this->getName(), 'imports', $builder, $data);
    }

    public function analyzePermissions(array &$permissions, $allPermissions, $isSecondRound = false)
    {
        parent::analyzePermissions($permissions, $allPermissions, $isSecondRound);

        //make sure the user has access to own leads as well if they have access to lists, notes or fields
        $viewPerms = ['viewown', 'viewother', 'full'];
        if (
            (!isset($permissions['leads']) || (array_intersect($viewPerms, $permissions['leads']) == $viewPerms)) &&
            (isset($permissions['lists']) || isset($permissions['fields']))
        ) {
            $permissions['leads'][] = 'viewown';
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getSynonym($name, $level)
    {
        if ('fields' === $name) {
            //set some synonyms
            switch ($level) {
                case 'publishown':
                case 'publishother':
                    $level = 'full';
                    break;
            }
        }

        return parent::getSynonym($name, $level);
    }
}
