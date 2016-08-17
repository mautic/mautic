<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MonitoringType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
            'label'      => 'mautic.core.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('networkType', 'choice', array(
            'label'      => 'mautic.social.monitoring.type.list',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'onchange' => 'Mautic.getNetworkFormAction(this)'
            ),
            'choices'     => $options['networkTypes'], // passed from the controller
            'empty_value' => 'mautic.core.form.chooseone'
        ));

        // if we have a network type value add in the form
        if (! empty($options['networkType']) && array_key_exists($options['networkType'], $options['networkTypes'])) {

            // get the values from the entity function
            $properties = $options['data']->getProperties();

            $builder->add('properties', $options['networkType'],
                array (
                    'label' => false,
                    'data'  => $properties
                )
            );
        }

        $builder->add('lists', 'leadlist_choices', array(
            'label'      => 'mautic.lead.lead.events.addtolists',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control'
            ),
            'multiple' => true,
            'expanded' => false
        ));

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'plugin:mauticSocial'
        ));

        $builder->add('buttons', 'form_buttons');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'MauticPlugin\MauticSocialBundle\Entity\Monitoring',
            ));

        // allow network types to be sent through - list
        $resolver->setRequired(array('networkTypes'));

        // allow the specific network type - single
        $resolver->setOptional(array('networkType'));
    }

    public function getName()
    {
        return "monitoring";
    }
}