<?php

namespace Mautic\PointBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class PointPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions(['points', 'triggers', 'groups', 'categories']);
    }

    public function getName(): string
    {
        return 'point';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('point', 'categories', $builder, $data);
        $this->addStandardFormFields('point', 'points', $builder, $data);
        $this->addStandardFormFields('point', 'triggers', $builder, $data);
        $this->addStandardFormFields('point', 'groups', $builder, $data);
    }
}
