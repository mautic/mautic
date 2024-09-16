<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class IntegrationCampaignsType.
 */
class IntegrationCampaignsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['campaignContactStatus' => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'integration_campaign_status';
    }
}
