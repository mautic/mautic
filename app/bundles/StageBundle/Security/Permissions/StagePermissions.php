<?php

namespace Mautic\StageBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class StagePermissions extends AbstractPermissions
{
    public const PERMISSION_VIEW    = 'stage:stages:view';

    public const PERMISSION_CREATE  = 'stage:stages:create';

    public const PERMISSION_EDIT    = 'stage:stages:edit';

    public const PERMISSION_DELETE  = 'stage:stages:delete';

    public const PERMISSION_PUBLISH = 'stage:stages:publish';

    /**
     * @param mixed[] $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

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
