<?php

namespace MauticPlugin\MauticSocialBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class MauticSocialPermissions.
 */
class MauticSocialPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addStandardPermissions('monitoring');
        $this->addExtendedPermissions('tweets');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName()
    {
        return 'mauticSocial';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('mauticSocial', 'categories', $builder, $data);
        $this->addStandardFormFields('mauticSocial', 'monitoring', $builder, $data);
        $this->addExtendedFormFields('mauticSocial', 'tweets', $builder, $data);
    }
}
