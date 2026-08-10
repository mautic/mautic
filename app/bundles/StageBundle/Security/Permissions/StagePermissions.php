<?php

namespace Mautic\StageBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class StagePermissions extends AbstractPermissions
{
    public const string PERMISSION_VIEW    = 'stage:stages:view';

    public const string PERMISSION_CREATE  = 'stage:stages:create';

    public const string PERMISSION_EDIT    = 'stage:stages:edit';

    public const string PERMISSION_DELETE  = 'stage:stages:delete';

    public const string PERMISSION_PUBLISH = 'stage:stages:publish';

    public function definePermissions(): void
    {
        $this->addStandardPermissions('stages');
        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'stage';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('stage', 'categories', $builder, $data);
        $this->addStandardFormFields('stage', 'stages', $builder, $data);
    }
}
