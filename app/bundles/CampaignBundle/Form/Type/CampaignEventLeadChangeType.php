<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 *
 * @extends AbstractType<mixed>
 */
class CampaignEventLeadChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['data']['action'] ?? 'added';
        $builder->add('action', ButtonGroupType::class, [
            'choices' => [
                'mautic.campaign.form.trigger_leadchanged_added'   => 'added',
                'mautic.campaign.form.trigger_leadchanged_removed' => 'removed',
            ],
            'expanded'          => true,
            'multiple'          => false,
            'label_attr'        => ['class' => 'control-label'],
            'label'             => 'mautic.campaign.form.trigger_leadchanged',
            'placeholder'       => false,
            'required'          => false,
            'data'              => $data,
        ]);

        $builder->add('campaigns', CampaignListType::class, [
            'label'      => 'mautic.campaign.form.limittocampaigns',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.campaign.form.limittocampaigns_descr',
            ],
            'required' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_leadchange';
    }
}
