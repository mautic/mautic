<?php

namespace Mautic\DynamicContentBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class DynamicContentPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('dynamiccontents');
    }

    public function getName(): string
    {
        return 'dynamiccontent';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('dynamiccontent', 'categories', $builder, $data);
        $this->addExtendedFormFields('dynamiccontent', 'dynamiccontents', $builder, $data);
    }
}
