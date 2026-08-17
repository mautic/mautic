<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class AssetPermissions extends AbstractPermissions
{
    public function __construct()
    {
        $this->addExtendedPermissions(['assets']);
        $this->addStandardPermissions(['categories']);
    }

    public function getName(): string
    {
        return 'asset';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('asset', 'categories', $builder, $data);
        $this->addExtendedFormFields('asset', 'assets', $builder, $data);
    }
}
