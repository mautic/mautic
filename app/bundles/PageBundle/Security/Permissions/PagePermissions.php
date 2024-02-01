<?php

namespace Mautic\PageBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class PagePermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('pages');
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('preference_center');
    }

    public function getName(): string
    {
        return 'page';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('page', 'categories', $builder, $data);
        $this->addExtendedFormFields('page', 'pages', $builder, $data);
        $this->addExtendedFormFields('page', 'preference_center', $builder, $data);
    }
}
