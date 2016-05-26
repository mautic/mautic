<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Form\Type;

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
 * Class FeedType
 *
 * @package Mautic\FeedBundle\Form\Type
 */
class FeedType extends AbstractType
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
        $builder->addEventSubscriber(new FormExitSubscriber('Feed.Feed', $options));

        $builder->add(
            'FeedUrl',
            'text',
            array(
                'label'      => 'URL',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );
        $builder->add(
            'ItemCount',
            'number',
            array(
                'label'      => 'Number of items per mail',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
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
                'data_class' => 'Mautic\FeedBundle\Entity\Feed'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Feedform";
    }
}
