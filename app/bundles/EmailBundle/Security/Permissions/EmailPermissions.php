<?php

namespace Mautic\EmailBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('emails');
        $this->permissions['emails']['sendtodnc'] = 1;
    }

    public function getName(): string
    {
        return 'email';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('email', 'categories', $builder, $data);
        $this->addExtendedFormFields('email', 'emails', $builder, $data);
    }

    /**
     * Adds the standard permission set of viewown, viewother, editown, editother, create, deleteown, deleteother,
     * publishown, publishother and full to the form builder.
     *
     * @param string               $bundle
     * @param string               $level
     * @param FormBuilderInterface $builder
     * @param mixed[]              $data
     * @param bool                 $includePublish
     */
    protected function addExtendedFormFields($bundle, $level, &$builder, $data, $includePublish = true): void
    {
        $choices = [
            'mautic.core.permissions.viewown'     => 'viewown',
            'mautic.core.permissions.viewother'   => 'viewother',
            'mautic.core.permissions.editown'     => 'editown',
            'mautic.core.permissions.editother'   => 'editother',
            'mautic.core.permissions.create'      => 'create',
            'mautic.core.permissions.deleteown'   => 'deleteown',
            'mautic.core.permissions.deleteother' => 'deleteother',
            'mautic.core.permissions.full'        => 'full',
            'mautic.email.send.dnc.label'         => 'sendtodnc',
        ];

        if ($includePublish) {
            $choices['mautic.core.permissions.publishown']   = 'publishown';
            $choices['mautic.core.permissions.publishother'] = 'publishother';
        }

        $builder->add(
            "$bundle:$level",
            PermissionListType::class,
            [
                'choices'           => $choices,
                'choices_as_values' => true,
                'label'             => $this->getLabel($bundle, $level),
                'data'              => (!empty($data[$level]) ? $data[$level] : []),
                'bundle'            => $bundle,
                'level'             => $level,
            ]
        );
    }
}
