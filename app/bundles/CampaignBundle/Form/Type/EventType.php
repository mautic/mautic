<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        $masks = array();

        $builder->add('name', 'text', array(
            'label'      => 'mautic.core.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        if ($options['data']['eventType'] == 'action' || $options['data']['eventType'] == 'condition') {
            $triggerMode = (empty($options['data']['triggerMode'])) ? 'immediate' : $options['data']['triggerMode'];
            $builder->add('triggerMode', 'button_group', array(
                'choices' => array(
                    'immediate' => 'mautic.campaign.form.type.immediate',
                    'interval'  => 'mautic.campaign.form.type.interval',
                    'date'      => 'mautic.campaign.form.type.date'
                ),
                'expanded'    => true,
                'multiple'    => false,
                'label_attr'  => array('class' => 'control-label'),
                'label'       => 'mautic.campaign.form.type',
                'empty_value' => false,
                'required'    => false,
                'attr'        => array(
                    'onchange' => 'Mautic.campaignToggleTimeframes();'
                ),
                'data'        => $triggerMode
            ));

            $builder->add('triggerDate', 'datetime', array(
                'label'      => false,
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-calendar',
                    'data-toggle' => 'datetime'
                ),
                'widget'     => 'single_text',
                'format'     => 'yyyy-MM-dd HH:mm'
            ));

            $data = (empty($options['data']['triggerInterval'])) ? 1 : $options['data']['triggerInterval'];
            $builder->add('triggerInterval', 'number', array(
                'label'      => false,
                'attr'       => array(
                    'class'    => 'form-control',
                    'preaddon' => 'symbol-hashtag'
                ),
                'data'       => $data
            ));

            $data = (!empty($options['data']['triggerIntervalUnit'])) ? $options['data']['triggerIntervalUnit'] : 'd';;

            $builder->add('triggerIntervalUnit', 'choice', array(
                'choices'     => array(
                    'i' => 'mautic.campaign.event.intervalunit.choice.i',
                    'h' => 'mautic.campaign.event.intervalunit.choice.h',
                    'd' => 'mautic.campaign.event.intervalunit.choice.d',
                    'm' => 'mautic.campaign.event.intervalunit.choice.m',
                    'y' => 'mautic.campaign.event.intervalunit.choice.y',
                ),
                'multiple'    => false,
                'label_attr'  => array('class' => 'control-label'),
                'label'       => false,
                'attr'        => array(
                    'class' => 'form-control'
                ),
                'empty_value' => false,
                'required'    => false,
                'data'        => $data
            ));
        }

        if (!empty($options['settings']['formType'])) {
            $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
            $formTypeOptions = array(
                'label' => false,
                'data'  => $properties
            );
            if (isset($options['settings']['formTypeCleanMasks'])) {
                $masks['properties'] = $options['settings']['formTypeCleanMasks'];
            }
            if (!empty($options['settings']['formTypeOptions'])) {
                $formTypeOptions = array_merge($formTypeOptions, $options['settings']['formTypeOptions']);
            }
            $builder->add('properties', $options['settings']['formType'], $formTypeOptions);
        }

        $builder->add('type', 'hidden');
        $builder->add('eventType', 'hidden');

        $builder->add('canvasSettings', 'campaignevent_canvassettings', array(
            'label' => false
        ));

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
            'save_onclick' => 'Mautic.submitCampaignEvent(event)',
            'apply_text' => false,
            'container_class' => 'bottom-form-buttons'
        ));

        $builder->add('campaignId', 'hidden', array(
            'mapped' => false
        ));

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('settings'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "campaignevent";
    }
}
