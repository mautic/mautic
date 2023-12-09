<?php

namespace MauticPlugin\MauticTagManagerBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class TagManagerPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('tagManager', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'tagManager';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('tagManager', 'tagManager', $builder, $data);
    }
}
