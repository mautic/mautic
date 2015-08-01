<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        $object = $options['integration_object'];
        $secretKeys = $object->getSecretKeys();
        foreach ($options['integration_keys'] as $key => $label) {
            $type = (in_array($key, $secretKeys)) ? 'password' : 'text';
            $builder->add($key, $type, array(
                'label'        => $label,
                'label_attr'   => array('class' => 'control-label'),
                'attr'         => array(
                    'class'       => 'form-control',
                    'placeholder' => ($type == 'password') ? '**************' : ''
                ),
                'required'     => false,
                'error_bubbling' => false
            ));
        }
        $object->appendToForm($builder, $options['data'], 'keys');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('integration_object', 'integration_keys'));
        $resolver->setOptional(array('secret_keys'));
        $resolver->setDefaults(array('secret_keys' => array()));
    }

    /**
     * @return string
     */
    public function getName() {
        return "integration_keys";
    }
}