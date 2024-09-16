<?php

namespace MauticPlugin\MauticTagManagerBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TagManagerPermissions.
 */
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
    public function getName()
    {
        return 'tagManager';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('tagManager', 'tagManager', $builder, $data);
    }
}
