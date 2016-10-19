<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardHitsInTimeWidgetType.
 */
class DashboardHitsInTimeWidgetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('flag', 'choice', [
                'label'   => 'mautic.page.visit.flag.filter',
                'choices' => [
                    ''                 => 'mautic.page.show.total.visits',
                    'unique'           => 'mautic.page.show.unique.visits',
                    'total_and_unique' => 'mautic.page.show.unique.and.total.visits',
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
    public function getName()
    {
        return 'page_dashboard_hits_in_time_widget';
    }
}
