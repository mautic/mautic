<?php

namespace MauticPlugin\MauticTagManagerBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class TagManagerPermissions extends AbstractPermissions
{
    /**
     * @param mixed[] $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('tagManager', false);
    }

    public function getName(): string
    {
        return 'tagManager';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('tagManager', 'tagManager', $builder, $data);
    }
}
