<?php

namespace Mautic\SmsBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class SmsPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('smses');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName()
    {
        return 'sms';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('sms', 'categories', $builder, $data);
        $this->addExtendedFormFields('sms', 'smses', $builder, $data);
    }
}
