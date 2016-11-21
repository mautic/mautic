<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormFieldCaptchaType.
 */
class FormFieldCaptchaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'captcha',
            'text',
            [
                'label'      => 'mautic.form.field.form.property_captcha',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'tooltip'     => 'mautic.form.field.help.captcha',
                    'placeholder' => 'mautic.form.field.help.captcha_placeholder',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'placeholder',
            'text',
            [
                'label'      => 'mautic.form.field.form.property_placeholder',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'errorMessage',
            'text',
            [
                'label'      => 'mautic.form.field.form.property_captchaerror',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'formfield_captcha';
    }
}
