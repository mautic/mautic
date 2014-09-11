<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EventType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class EventType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('name', 'text', array(
            'label'      => 'mautic.campaign.event.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.campaign.event.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        if ($options['campaignType'] == 'date') {
            $builder->add('fireDate', 'text', array(
                'label'      => 'mautic.campaign.event.firedate',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control',
                    'data-toggle' => 'datetime'
                )
            ));
        } else {
            $builder->add('fireInterval', 'number', array(
                'label'      => 'mautic.campaign.event.fireinterval',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control',
                    'preaddon' => 'symbol-hashtag'
                )
            ));

            $builder->add('fireIntervalUnit', 'choice', array(
                'choices' => array(
                    'i'  => 'mautic.campaign.event.intervalunit.minute',
                    'h'  => 'mautic.campaign.event.intervalunit.hour',
                    'd'  => 'mautic.campaign.event.intervalunit.day',
                    'm'  => 'mautic.campaign.event.intervalunit.month',
                    'y'  => 'mautic.campaign.event.intervalunit.year',
                ),
                'multiple'    => false,
                'label_attr'  => array('class' => 'control-label'),
                'label'       => false,
                'attr'        => array('class' => 'form-control'),
                'empty_value' => false,
                'required'    => false
            ));
        }

        if (!empty($options['settings']['formType'])) {
            $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
            $builder->add('properties', $options['settings']['formType'], array(
                'label' => false,
                'data'  => $properties
            ));
        }

        $builder->add('type', 'hidden');

        $update = !empty($properties);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add('buttons', 'form_buttons', array(
            'save_text' => $btnValue,
            'save_icon' => $btnIcon,
            'apply_text' => false,
            'container_class' => 'bottom-campaignevent-buttons'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('campaignType'));
        $resolver->setRequired(array('settings'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignevent";
    }
}