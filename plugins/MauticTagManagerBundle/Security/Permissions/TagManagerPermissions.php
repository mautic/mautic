<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TagManagerPermissions.
 */
class TagManagerPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('tagManager', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tagManager';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('tagManager', 'tagManager', $builder, $data);
    }
}
