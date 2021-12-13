<?php

namespace Mautic\LeadBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class LeadPermissions extends AbstractPermissions
{
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

        $this->addExtendedPermissions('lists', false);
        $this->addExtendedPermissions('leads', false);
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

        $this->addStandardFormFields($this->getName(), 'imports', $builder, $data);
    }

    public function analyzePermissions(array &$permissions, $allPermissions, $isSecondRound = false)
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
