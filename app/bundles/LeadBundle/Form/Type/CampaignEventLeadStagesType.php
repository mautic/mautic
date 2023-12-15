<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\StageBundle\Form\Type\StageListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignEventLeadStagesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'stages',
            StageListType::class,
            [
                'label'       => 'mautic.lead.lead.field.stage',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'required'    => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'campaignevent_lead_stages';
    }
}
