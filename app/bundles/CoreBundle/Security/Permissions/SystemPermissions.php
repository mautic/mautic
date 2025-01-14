<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AssetPermissions.
 */
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
    public function getName()
    {
        return 'core';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('core', 'themes', $builder, $data);
    }
}
