<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NotificationPermissions.
 */
class DynamicContentPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('dynamiccontents');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName()
    {
        return 'dynamicContent';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('dynamicContent', 'categories', $builder, $data);
        $this->addExtendedFormFields('dynamicContent', 'dynamiccontents', $builder, $data);
    }
}
