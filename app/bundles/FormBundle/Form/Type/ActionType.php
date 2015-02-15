<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ActionType
 */
class ActionType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masks = array('description' => 'html');

        $builder->add('name', 'text', array(
            'label'      => 'mautic.core.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $properties      = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
        $formType        = $options['settings']['formType'];
        $formTypeOptions = array(
            'label'  => false,
            'data'   => $properties,
            'attr'   => array(
                'data-formid' => $options['formId'] //sneaky way of feeding the formId without requiring the option
            ));
        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }
        if (!empty($options['settings']['formTypeOptions'])) {
            // Ensure that attr is not overwritten
            if (isset($options['settings']['formTypeOptions']['attr'])) {
                $options['settings']['formTypeOptions']['attr']['data-formid'] = $options['formId'];
            }
            $formTypeOptions = array_merge($formTypeOptions, $options['settings']['formTypeOptions']);
        }
        $builder->add('properties', $formType, $formTypeOptions);

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
            'container_class' => 'bottom-form-buttons'
        ));

        $builder->add('formId', 'hidden', array(
            'mapped' => false
        ));

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'settings' => false
        ));

        $resolver->setRequired(array('settings', 'formId'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formaction";
    }
}
