<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaKeysType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class KeysType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        foreach ($options['integration_keys'] as $key => $label) {
            $type = ($key == $options['secret_key']) ? 'password' : 'text';
            $builder->add($key, $type, array(
                'label'        => $label,
                'label_attr'   => array('class' => 'control-label'),
                'attr'         => array(
                    'class'       => 'form-control',
                    'placeholder' => ($type == 'password') ? '**************' : ''
                )
            ));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('integration_keys'));
        $resolver->setOptional(array('secret_key'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "integration_keys";
    }
}