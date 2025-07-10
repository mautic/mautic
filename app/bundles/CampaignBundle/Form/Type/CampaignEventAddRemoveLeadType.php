<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CampaignBundle\Form\Validator\Constraints\InfiniteLoop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CampaignEventAddRemoveLeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
            'constraints'      => [new InfiniteLoop()],
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

    public function getBlockPrefix(): string
    {
        return 'campaignevent_addremovelead';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'include_this' => false,
        ]);
    }
}
