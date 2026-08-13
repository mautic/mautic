<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class PointPermissions extends AbstractPermissions
{
    public function __construct()
    {
        $this->addStandardPermissions(['points', 'triggers', 'groups', 'categories', 'insights']);
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
        $this->addStandardFormFields('point', 'insights', $builder, $data);
    }
}
