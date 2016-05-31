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
            'nextShoot',
            'text',
            array(
                'label'      => 'Beginning Date',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'mapped' => false,
                'required' => false,
                'data' => (new \DateTime('now'))->format('Y-m-d H:i')
            )
        );
        $builder->add(
            'interval',
            'number',
            array(
                'label'      => 'Periodicity',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'mapped' => false,
                'required' => false
            )
        );
        $builder->add(
            'intervalUnit',
            'choice',
            array(
                'label'      => 'Unit',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'mapped' => false,
                'choices'  => array(
                    'd' => 'Days',
                    'w' => 'Weeks',
                    'm' => 'Months'
                ),
                'multiple' => false,
                'placeholder' => false,
                'required' => false
            )
        );
        $builder->add(
            'DaysOfWeek',
            'choice',
            array(
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'mapped' => false,
                'choices'  => array(
                    0 => 'Monday',
                    1 => 'Tuesday',
                    2 => 'Wednesday',
                    3 => 'Thursday',
                    4 => 'Friday',
                    5 => 'Saturday',
                    6 => 'Sunday'
                ),
                'multiple' => true,
                'required' => false
            )
        );
//         $builder->add(
//             'periodicity_next_shoot',
//             'datetime',
//             array(
//                 'widget' => 'single_text',
//                 'label'      => 'First send date & time',
//                 'label_attr' => array('class' => 'control-label'),
//                 'attr'       => array('class' => 'form-control'),
//                 'format' => 'dd/MM/yyyy hh:mm',
//                 'data' => new \DateTime('now')
//             )
//         );
//         $builder->add(
//             'periodicity_interval',
//             'number',
//             array(
//                 'label'      => 'Periodicity (number of day)',
//                 'label_attr' => array('class' => 'control-label'),
//                 'attr'       => array('class' => 'form-control')
//             )
//         );
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
