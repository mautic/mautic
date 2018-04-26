<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormPermissions.
 */
class FormPermissions extends AbstractPermissions
{
    /**
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addCustomPermission('export', ['disable' => 1024]);
        $this->addExtendedPermissions('forms');
        $this->addStandardPermissions('categories');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields($this->getName(), 'categories', $builder, $data);
        $this->addExtendedFormFields($this->getName(), 'forms', $builder, $data);
        $this->addCustomFormFields($this->getName(), 'export', $builder, 'mautic.core.permissions.export', ['disable' => 'mautic.core.permissions.disable'], $data);
    }
}
