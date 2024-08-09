<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class IntegrationCampaignsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'campaign_member_status',
            ChoiceType::class,
            [
                'choices'           => array_flip($options['campaignContactStatus']),
                'attr'              => [
                    'class' => 'form-control', ],
                'label'    => 'mautic.plugin.integration.campaigns.member.status',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            ['campaignContactStatus' => []]);
    }

    public function getBlockPrefix()
    {
        return 'integration_campaign_status';
    }
}
