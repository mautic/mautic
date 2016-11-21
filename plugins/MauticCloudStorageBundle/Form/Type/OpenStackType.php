<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class OpenStackType.
 */
class OpenStackType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('serviceUrl', 'url', [
            'label'      => 'mautic.integration.OpenStack.service.url',
            'required'   => true,
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'tooltip' => 'mautic.integration.OpenStack.service.url.tooltip',
                'class'   => 'form-control',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cloudstorage_openstack';
    }
}
