<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PointPermissions.
 */
class PointPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('points');
        $this->addStandardPermissions('triggers');
        $this->addStandardPermissions('categories');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'point';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('point', 'categories', $builder, $data);
        $this->addStandardFormFields('point', 'points', $builder, $data);
        $this->addStandardFormFields('point', 'triggers', $builder, $data);
    }
}
