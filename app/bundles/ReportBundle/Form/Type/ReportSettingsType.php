<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'showGraphsAboveTable',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.report.report.form.display.graphs.above.table',
                'attr'  => [
                    'class' => 'filter-value',
                ],
                'data' => !empty($options['data']['showGraphsAboveTable']) ? $options['data']['showGraphsAboveTable'] : false,
            ]
        );

        $builder->add(
            'showDynamicFilters',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.report.report.form.display.show.dynamic.filters',
                'attr'  => [
                    'class' => 'filter-value',
                ],
                'data' => !empty($options['data']['showDynamicFilters']) ? $options['data']['showDynamicFilters'] : false,
            ]
        );

        $builder->add(
            'hideDateRangeFilter',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.report.report.form.display.hide.date.ranges',
                'attr'  => [
                    'class' => 'filter-value',
                ],
                'data' => !empty($options['data']['hideDateRangeFilter']) ? $options['data']['hideDateRangeFilter'] : false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'report_settings';
    }
}
