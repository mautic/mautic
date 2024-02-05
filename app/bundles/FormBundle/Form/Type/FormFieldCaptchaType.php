<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldCaptchaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'captcha',
            TextType::class,
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
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.property_placeholder',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'errorMessage',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.property_captchaerror',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'formfield_captcha';
    }
}
