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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FieldType
 */
class FieldType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('label', 'text', array(
            'label'      => 'mautic.form.field.form.label',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'constraints' => array(
                new Assert\NotBlank(
                    array('message' => 'mautic.form.field.label.notblank')
                )
            )
        ));

        $addHelpMessage =
        $addShowLabel =
        $addDefaultValue =
        $addLabelAttributes =
        $addInputAttributes =
        $addIsRequired = true;

        if (!empty($options['customParameters'])) {
            $customParams =& $options['customParameters'];
            $builder->add('properties', $customParams['formType'], array(
                'required'   => false,
                'label'      => false
            ));
            $addFields = array(
                'addHelpMessage',
                'addShowLabel',
                'addDefaultValue',
                'addLabelAttributes',
                'addInputAttributes',
                'addIsRequired');
            foreach ($addFields as $f) {
                if (isset($customParams['builderOptions'][$f])) {
                    $$f = (boolean) $customParams['builderOptions'][$f];
                }
            }
        } else {
            $type = $options['data']['type'];
            switch ($type) {
                case 'select':
                case 'country':
                    $builder->add('properties', 'formfield_select', array(
                        'field_type' => $type,
                        'label'      => false,
                        'parentData' => $options['data']
                    ));
                    break;
                case 'checkboxgrp':
                case 'radiogrp':
                    $builder->add('properties', 'sortablelist', array(
                        'label'      => 'mautic.core.form.list',
                        'label_attr' => array('class' => 'control-label'),
                    ));
                    break;
                case 'freetext':
                    $builder->add('properties', 'formfield_text', array(
                        'required' => false,
                        'label'    => false
                    ));
                    $addHelpMessage = $addDefaultValue = $addIsRequired = false;
                    break;
                case 'button':
                    $builder->add('properties', 'formfield_button', array(
                        'label' => false
                    ));
                    $addHelpMessage = $addShowLabel = $addDefaultValue = $addLabelAttributes = $addIsRequired = false;
                    break;
                case 'hidden':
                    $addHelpMessage = $addShowLabel = $addLabelAttributes = $addIsRequired = false;
                    break;
                case 'date':
                case 'email':
                case 'number':
                case 'tel':
                case 'text':
                case 'url':
                    $builder->add('properties', 'formfield_placeholder', array(
                        'label' => false
                    ));
                    break;
                case 'captcha':
                    $builder->add('properties', 'formfield_captcha', array(
                        'label' => false
                    ));
                    $addShowLabel = $addIsRequired = $addDefaultValue = false;
                    break;
            }
        }

        if ($addShowLabel) {
            $default = (!isset($options['data']['showLabel'])) ? true : (boolean) $options['data']['showLabel'];
            $builder->add('showLabel', 'button_group', array(
                'label'       => 'mautic.form.field.form.showlabel',
                'data'        => $default
            ));
        }

        if ($addDefaultValue) {
            $builder->add('defaultValue', 'text', array(
                'label'      => 'mautic.form.field.form.defaultvalue',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            ));
        }

        if ($addHelpMessage) {
            $builder->add('helpMessage', 'text', array(
                'label'      => 'mautic.form.field.form.helpmessage',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            ));
        }

        if ($addIsRequired) {
            $default = (!isset($options['data']['isRequired'])) ? false : (boolean) $options['data']['isRequired'];
            $builder->add('isRequired', 'yesno_button_group', array(
                'label'       => 'mautic.lead.field.form.isrequired',
                'data'        => $default
            ));

            $builder->add('validationMessage', 'text', array(
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            ));
        }

        if ($addLabelAttributes) {
            $builder->add('labelAttributes', 'text', array(
                'label'      => 'mautic.form.field.form.labelattr',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.attr'
                ),
                'required'   => false
            ));
        }

        if ($addInputAttributes) {
            $builder->add('inputAttributes', 'text', array(
                'label'      => 'mautic.form.field.form.inputattr',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.help.attr'
                ),
                'required'   => false
            ));
        }

        $builder->add('type', 'hidden');

        $update = (!empty($options['data']['properties'])) ? true : false;
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
            'customParameters' => false
        ));

        $resolver->setOptional(array('customParameters'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formfield";
    }
}
