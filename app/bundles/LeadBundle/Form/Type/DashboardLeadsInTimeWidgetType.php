<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class DashboardLeadsInTimeWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'flag',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.list.filter',
                'choices'           => [
                    'mautic.lead.show.all'                               => '',
                    'mautic.lead.show.identified'                        => 'identified',
                    'mautic.lead.show.anonymous'                         => 'anonymous',
                    'mautic.lead.show.identified.vs.anonymous'           => 'identifiedVsAnonymous',
                    'mautic.lead.show.top'                               => 'top',
                    'mautic.lead.show.top.leads.identified.vs.anonymous' => 'topIdentifiedVsAnonymous',
                ],
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_dashboard_leads_in_time_widget';
    }
}
