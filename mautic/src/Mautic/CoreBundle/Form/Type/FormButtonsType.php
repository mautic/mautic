<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormButtonsType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {

        if (!empty($options['apply_text'])) {
            $builder->add('apply', 'submit', array(
                'label' => $options['apply_text'],
                'attr'  => array(
                    'class' => $options['apply_class'],
                    'icon'  => $options['apply_icon']
                )
            ));
        }

        if (!empty($options['save_text'])) {
            $builder->add('save', 'submit', array(
                'label' => $options['save_text'],
                'attr'  => array(
                    'class' => $options['save_class'],
                    'icon'  => $options['save_icon']
                )
            ));
        }

        if (!empty($options['cancel_text'])) {
            $builder->add('cancel', 'submit', array(
                'label' => $options['cancel_text'],
                'attr'  => array(
                    'class' => $options['cancel_class'],
                    'icon'  => $options['cancel_icon']
                )
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'apply_text'   => 'mautic.core.form.apply',
            'apply_icon'   => 'fa fa-check',
            'apply_class'  => 'btn btn-primary',
            'save_text'    => 'mautic.core.form.save',
            'save_icon'    => 'fa fa-save padding-sm-right',
            'save_class'   => 'btn btn-primary',
            'cancel_text'  => 'mautic.core.form.cancel',
            'cancel_icon'  => 'fa fa-times padding-sm-right',
            'cancel_class' => 'btn btn-danger',
            'mapped'       => false,
            'label'        => false,
            'required'     => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form_buttons';
    }
}