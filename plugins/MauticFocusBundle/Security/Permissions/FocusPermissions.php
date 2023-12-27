<?php

namespace MauticPlugin\MauticFocusBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class FocusPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('items');
    }

    public function getName(): string
    {
        return 'focus';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('focus', 'categories', $builder, $data);
        $this->addExtendedFormFields('focus', 'items', $builder, $data);
    }
}
