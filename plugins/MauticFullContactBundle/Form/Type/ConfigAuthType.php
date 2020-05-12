<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigAuthType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'apikey',
            TextType::class,
            [
                'label'    => 'mautic.integration.fullcontact.apikey',
                'required' => true,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ]
        );
        $builder->add(
          'auto_update',
          YesNoButtonGroupType::class,
          [
            'label' => 'mautic.plugin.fullcontact.auto_update',
            'data'  => (isset($data['auto_update'])) ? (bool) $data['auto_update'] : false,
            'attr'  => [
              'tooltip' => 'mautic.plugin.fullcontact.auto_update.tooltip',
            ],
          ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(
          [
            'integration' => null,
          ]
        );
    }
}
