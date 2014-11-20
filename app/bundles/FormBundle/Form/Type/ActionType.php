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
 */
class ActionType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.form.action.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.form.action.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $formType = $options['settings']['formType'];

        $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
        $builder->add('properties', $formType, array(
            'label'  => false,
            'data'   => $properties,
            'attr'   => array(
                'data-formid' => $options['formId'] //sneaky way of feeding the formId without requiring the option
            )
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
            'apply_text' => false,
            'container_class' => 'bottom-formaction-buttons'
        ));

        $builder->add('formId', 'hidden', array(
            'mapped' => false
        ));

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
