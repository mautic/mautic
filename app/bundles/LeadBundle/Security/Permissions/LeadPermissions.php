<?php

namespace Mautic\LeadBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

class LeadPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->permissions = [
            'lists' => [
                'viewother'   => 2,
                'viewown'     => 4,
                'editother'   => 8,
                'deleteother' => 64,
                'full'        => 1024,
            ],
            'fields' => [
                'full' => 1024,
                'view' => 1,
            ],
        ];
        $this->addExtendedPermissions('leads', false);
        $this->addStandardPermissions('imports');
    }

    public function getName(): string
    {
        return 'lead';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addExtendedFormFields('lead', 'leads', $builder, $data, false);

        $builder->add(
            'lead:lists',
            PermissionListType::class,
            [
                'choices' => [
                    'mautic.core.permissions.viewother'   => 'viewother',
                    'mautic.core.permissions.editother'   => 'editother',
                    'mautic.core.permissions.deleteother' => 'deleteother',
                    'mautic.core.permissions.full'        => 'full',
                ],
                'label'             => 'mautic.lead.permissions.lists',
                'data'              => (!empty($data['lists']) ? $data['lists'] : []),
                'bundle'            => 'lead',
                'level'             => 'lists',
            ]
        );

        $builder->add(
            'lead:fields',
            PermissionListType::class,
            [
                'choices' => [
                    'mautic.core.permissions.manage' => 'full',
                    'mautic.core.permissions.view'   => 'view',
                ],
                'label'             => 'mautic.lead.permissions.fields',
                'data'              => (!empty($data['fields']) ? $data['fields'] : []),
                'bundle'            => 'lead',
                'level'             => 'fields',
            ]
        );

        $this->addStandardFormFields($this->getName(), 'imports', $builder, $data);
    }

    public function analyzePermissions(array &$permissions, $allPermissions, $isSecondRound = false): bool
    {
        parent::analyzePermissions($permissions, $allPermissions, $isSecondRound);

        // make sure the user has access to own leads as well if they have access to lists, notes or fields
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
            // set some synonyms
            switch ($level) {
                case 'publishown':
                case 'publishother':
                    $level = 'full';
                    break;
            }
        }

        if ('lists' === $name) {
            switch ($level) {
                case 'view':
                case 'viewown':
                    $name = 'leads';
                    break;
            }
        }

        return parent::getSynonym($name, $level);
    }
}
