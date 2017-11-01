<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ReportSettingsType.
 */
class ReportSettingsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'showGraphsAboveTable',
            'yesno_button_group',
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
            'yesno_button_group',
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
            'yesno_button_group',
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
    public function getName()
    {
        return 'report_settings';
    }
}
