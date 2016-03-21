<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EmailOpenType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailOpenType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $defaultOptions = array(
            'label'      => 'mautic.email.open.limittoemails',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.email.open.limittoemails_descr'
            ),
            'required'   => false
        );

        if (isset($options['list_options'])) {
            if (isset($options['list_options']['attr'])) {
                $defaultOptions['attr'] = array_merge($defaultOptions['attr'], $options['list_options']['attr']);
                unset($options['list_options']['attr']);
            }

            $defaultOptions = array_merge($defaultOptions, $options['list_options']);
        }

        $builder->add('emails', 'email_list', $defaultOptions);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('list_options'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "emailopen_list";
    }
}