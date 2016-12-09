<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DeviceType.
 */
class DeviceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('device', 'text');
        $builder->add('deviceOsName', 'text');
        $builder->add('deviceOsShortName', 'text');
        $builder->add('deviceOsVersion', 'text');
        $builder->add('deviceOsPlatform', 'text');
        $builder->add('deviceModel', 'text');
        $builder->add('deviceBrand', 'text');

        $builder->add('buttons', 'form_buttons', [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Mautic\LeadBundle\Entity\LeadDevice',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leaddevice';
    }
}
