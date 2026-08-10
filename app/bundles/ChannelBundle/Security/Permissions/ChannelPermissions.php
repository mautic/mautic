<?php

namespace Mautic\ChannelBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class ChannelPermissions extends AbstractPermissions
{
    public function __construct()
    {
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('messages');
    }

    public function getName(): string
    {
        return 'channel';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields($this->getName(), 'categories', $builder, $data);
        $this->addExtendedFormFields($this->getName(), 'messages', $builder, $data);
    }
}
