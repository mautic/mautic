<?php

namespace Mautic\SmsBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

final class SmsPermissions extends AbstractPermissions
{
    public function __construct()
    {
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('smses');
    }

    public function getName(): string
    {
        return 'sms';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('sms', 'categories', $builder, $data);
        $this->addExtendedFormFields('sms', 'smses', $builder, $data);
    }
}
