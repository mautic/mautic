<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null != $options['integration']) {
            $options['integration']->appendToForm($builder, $options['data'], 'integration');
        }

        if (!empty($options['campaigns'])) {
            $builder->add(
                'campaigns',
                ChoiceType::class,
                [
                    'choices' => array_flip($options['campaigns']),
                    'attr'    => [
                        'class' => 'form-control', 'onchange' => 'Mautic.getIntegrationCampaignStatus(this);', ],
                    'label'             => 'mautic.plugin.integration.campaigns',
                    'placeholder'       => 'mautic.plugin.config.campaign.member.chooseone',
                    'required'          => false,
                    ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['integration']);
        $resolver->setDefaults([
            'label'     => false,
            'campaigns' => [],
        ]);
    }
}
