<?php

namespace Mautic\StageBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class StagePermissions extends AbstractPermissions
{
    public function __construct($params)
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
