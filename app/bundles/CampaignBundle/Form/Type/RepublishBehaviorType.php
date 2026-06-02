<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CampaignBundle\Enum\RepublishBehavior;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RepublishBehaviorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'                 => 'mautic.campaignconfig.campaign_republish_behavior',
            'label_attr'            => ['class' => 'control-label'],
            'required'              => false,
            'include_global_option' => false,
            'attr'                  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.campaignconfig.campaign_republish_behavior_tooltip',
            ],
        ]);

        $resolver->setNormalizer('choices', fn ($options) => $options['include_global_option']
                ? ['mautic.campaignconfig.campaign_republish_behavior.use_global' => null] + RepublishBehavior::getChoices()
                : RepublishBehavior::getChoices()
        );

        $resolver->setAllowedTypes('include_global_option', 'bool');
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
