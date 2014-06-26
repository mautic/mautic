<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class ActionType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('name', 'text', array(
            'label'      => 'mautic.form.action.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'length' => 50),
            'required'   => false
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.form.action.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $formType = $options['settings']['formType'];

        $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
        $builder->add('properties', $formType, array(
           'label' => false,
           'data'  => $properties
        ));

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
            'apply_text' => false
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
        $resolver->setDefaults(array(
            'settings' => false
        ));

        $resolver->setRequired(array('settings'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "formaction";
    }
}