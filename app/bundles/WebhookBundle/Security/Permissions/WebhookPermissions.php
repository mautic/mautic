<?php

namespace Mautic\WebhookBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class WebhookPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('webhooks');
        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'webhook';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('webhook', 'categories', $builder, $data);
        $this->addExtendedFormFields('webhook', 'webhooks', $builder, $data);
    }
}
