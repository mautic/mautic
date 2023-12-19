<?php

namespace MauticPlugin\MauticSocialBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class MauticSocialPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addStandardPermissions('monitoring');
        $this->addExtendedPermissions('tweets');
    }

    public function getName(): string
    {
        return 'mauticSocial';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('mauticSocial', 'categories', $builder, $data);
        $this->addStandardFormFields('mauticSocial', 'monitoring', $builder, $data);
        $this->addExtendedFormFields('mauticSocial', 'tweets', $builder, $data);
    }
}
