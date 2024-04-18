<?php

namespace Mautic\FormBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class FormPermissions extends AbstractPermissions
{
    /**
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('forms');
        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'form';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('form', 'categories', $builder, $data);
        $this->addExtendedFormFields('form', 'forms', $builder, $data);
    }
}
