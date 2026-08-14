<?php

namespace Mautic\PluginBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class PluginPermissions extends AbstractPermissions
{
    public function __construct()
    {
        $this->addManagePermission('plugins');
    }

    public function getName(): string
    {
        return 'plugin';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addManageFormFields('plugin', 'plugins', $builder, $data);
    }
}
