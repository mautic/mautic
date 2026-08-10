<?php

namespace Mautic\CoreBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

final class SystemPermissions extends AbstractPermissions
{
    public function definePermissions(): void
    {
        $this->addStandardPermissions('themes');
    }

    public function getName(): string
    {
        return 'core';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('core', 'themes', $builder, $data);
    }
}
