<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardHitsInTimeWidgetType.
 */
class DashboardHitsInTimeWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'page_dashboard_hits_in_time_widget';
    }
}
