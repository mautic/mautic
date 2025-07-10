<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class DashboardHitsInTimeWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('flag', ChoiceType::class, [
            'label'   => 'mautic.page.visit.flag.filter',
            'choices' => [
                'mautic.page.show.total.visits'            => '',
                'mautic.page.show.unique.visits'           => 'unique',
                'mautic.page.show.unique.and.total.visits' => 'total_and_unique',
            ],
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
            'empty_data'        => '',
            'required'          => false,
        ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'page_dashboard_hits_in_time_widget';
    }
}
