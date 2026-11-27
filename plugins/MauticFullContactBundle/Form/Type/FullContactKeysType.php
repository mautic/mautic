<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
final class FullContactKeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Do not rename the apikey field. fullcontact.js depends on it.
        $builder->add(
            'apikey',
            TextType::class,
            [
                'label'       => 'mautic.integration.fullcontact.apikey',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'constraints' => [new NotBlank()],
            ]
        );

        $builder->add(
            'test_api',
            ButtonType::class,
            [
                'label' => 'mautic.plugin.fullcontact.test_api',
                'attr'  => [
                    'class'   => 'btn btn-primary',
                    'style'   => 'margin-bottom: 10px',
                    'onclick' => 'Mautic.testFullContactApi(this)',
                ],
            ]
        );

        $builder->add(
            'stats',
            TextareaType::class,
            [
                'label'      => 'mautic.plugin.fullcontact.stats',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'    => 'form-control',
                    'rows'     => '6',
                    'readonly' => 'readonly',
                ],
            ]
        );

        $builder->add(
            'auto_update',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.plugin.fullcontact.auto_update',
                'attr'  => [
                    'tooltip' => 'mautic.plugin.fullcontact.auto_update.tooltip',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
