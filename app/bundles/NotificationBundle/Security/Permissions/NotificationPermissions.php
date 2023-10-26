<?php

namespace Mautic\NotificationBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NotificationPermissions.
 */
class NotificationPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('notifications');
        $this->addExtendedPermissions('mobile_notifications');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('notification', 'categories', $builder, $data);
        $this->addExtendedFormFields('notification', 'notifications', $builder, $data);
        $this->addExtendedFormFields('notification', 'mobile_notifications', $builder, $data);
    }
}
