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

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\LeadDevice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('device', 'text');
        $builder->add('deviceOsName', 'text');
        $builder->add('deviceOsShortName', 'text');
        $builder->add('deviceOsVersion', 'text');
        $builder->add('deviceOsPlatform', 'text');
        $builder->add('deviceModel', 'text');
        $builder->add('deviceBrand', 'text');

        $builder->add('buttons', FormButtonsType::class, [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LeadDevice::class,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leaddevice';
    }
}
