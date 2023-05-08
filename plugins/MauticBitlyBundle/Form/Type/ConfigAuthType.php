<?php

declare(strict_types=1);

namespace MauticPlugin\MauticBitlyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigAuthType extends AbstractType
{
    public const ACCESS_TOKEN = 'access_token';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            self::ACCESS_TOKEN,
            TextType::class,
            [
                'label'      => 'mautic.bitly.access_token',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints'       => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(
            [
                'integration' => null,
            ]
        );
    }
}
