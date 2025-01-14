<?php

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CampaignEventAddRemoveLeadType.
 */
class CampaignEventAddRemoveLeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('addTo', CampaignListType::class, [
            'label'      => 'mautic.campaign.form.addtocampaigns',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'required'         => false,
            'include_this'     => $options['include_this'],
            'this_translation' => 'mautic.campaign.form.thiscampaign_restart',
        ]);

        $builder->add('removeFrom', CampaignListType::class, [
            'label'      => 'mautic.campaign.form.removefromcampaigns',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'required'     => false,
            'include_this' => $options['include_this'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'campaignevent_addremovelead';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'include_this' => false,
        ]);
    }
}
