<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardHitsInTimeWidgetType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class DashboardHitsInTimeWidgetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', 'integer', array(
                'label'      => 'mautic.core.number',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'empty_data' => '30'
            )
        );

        $builder->add('timeUnit', 'choice', array(
                'label'   => 'mautic.core.time.unit',
                'choices' => array(
                    's' => 'mautic.core.time.seconds',
                    'i' => 'mautic.core.time.minutes',
                    'H' => 'mautic.core.time.hours',
                    'd' => 'mautic.core.time.days',
                    'W' => 'mautic.core.time.weeks',
                    'm' => 'mautic.core.time.months',
                    'Y' => 'mautic.core.time.years'
                ),
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'empty_data' => 'd',
                'required'   => false
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "page_dashboard_hits_in_time_widget";
    }
}
