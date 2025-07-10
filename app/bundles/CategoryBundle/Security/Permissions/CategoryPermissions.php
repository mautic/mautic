<?php

namespace Mautic\CategoryBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('categories');
    }

    public function getName(): string
    {
        return 'category';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('category', 'categories', $builder, $data);
    }
}
