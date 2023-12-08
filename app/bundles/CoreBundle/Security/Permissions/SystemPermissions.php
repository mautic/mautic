<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

class SystemPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('themes');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'core';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('core', 'themes', $builder, $data);
    }
}
