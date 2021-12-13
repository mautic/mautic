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
}
