<?php

namespace Mautic\ChannelBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class ChannelPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('messages');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName(): string
    {
        return 'channel';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields($this->getName(), 'categories', $builder, $data);
        $this->addExtendedFormFields($this->getName(), 'messages', $builder, $data);
    }
}
