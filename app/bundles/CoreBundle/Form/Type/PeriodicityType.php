<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\EmojiToShortTransformer;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PeriodicityType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class PeriodicityType extends AbstractType
{

    /**
     * @param MauticFactory $factory
     */
    public function __construct()
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html', 'customHtml' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('Periodicity.Periodicity', $options));

        $builder->add(
            'triggerDate',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.periodicity.form.triggerdate',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control',
                                      'data-toggle' => 'datetime'),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required' => false
            )
        );

        $builder->add('triggerMode', 'button_group', array(
            'choices'          => array(
                'timeInterval' => 'mautic.core.periodicity.form.interval',
                'weekDays'     => 'mautic.core.periodicity.form.days_of_week'
            ),
            'label_attr'       => array('class' => 'control-label'),
            'label'            => 'Kind of periodicity',
            'attr'             => array(
                'onchange'     => 'Mautic.feedToggleTriggerMode();'
            )
        ));

        $builder->add(
            'triggerInterval',
            'number',
            array(
                'label'      => 'mautic.core.periodicity.form.interval',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required' => false
            )
        );
        $builder->add(
            'triggerIntervalUnit',
            'choice',
            array(
                'label'      => 'mautic.core.periodicity.form.interval_unit',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'choices'  => array(
                    'd' => 'mautic.core.periodicity.form.unit.days',
                    'w' => 'mautic.core.periodicity.form.unit.weeks',
                    'm' => 'mautic.core.periodicity.form.unit.months'
                ),
                'multiple' => false,
                'placeholder' => false,
                'required' => false
            )
        );
        $builder->add(
            'weekDays',
            'choice',
            array(
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'choices'  => array(
                    0 => 'mautic.core.periodicity.form.days_of_week.monday',
                    1 => 'mautic.core.periodicity.form.days_of_week.tuesday',
                    2 => 'mautic.core.periodicity.form.days_of_week.wednesday',
                    3 => 'mautic.core.periodicity.form.days_of_week.thursday',
                    4 => 'mautic.core.periodicity.form.days_of_week.friday',
                    5 => 'mautic.core.periodicity.form.days_of_week.saturday',
                    6 => 'mautic.core.periodicity.form.days_of_week.sunday'
                ),
                'mapped'   => false,
                'multiple' => true,
                'required' => false
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Mautic\CoreBundle\Entity\Periodicity'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Periodicityform";
    }
}
