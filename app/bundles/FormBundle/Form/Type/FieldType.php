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
        // Populate settings
        $cleanMasks = array(
            'labelAttributes'     => 'string',
            'inputAttributes'     => 'string',
            'containerAttributes' => 'string'
        );

        $addHelpMessage =
        $addShowLabel =
        $addDefaultValue =
        $addLabelAttributes =
        $addInputAttributes =
        $addContainerAttributes =
        $addLeadFieldList =
        $addSaveResult =
        $addIsRequired = true;

        if (!empty($options['customParameters'])) {
            $type = 'custom';

            $customParams    =& $options['customParameters'];
            $formTypeOptions = array(
                'required' => false,
                'label'    => false
            );
            if (!empty($customParams['formTypeOptions'])) {
                $formTypeOptions = array_merge($formTypeOptions, $customParams['formTypeOptions']);
            }

            $addFields = array(
                'labelText',
                'addHelpMessage',
                'addShowLabel',
                'labelText',
                'addDefaultValue',
                'addLabelAttributes',
                'labelAttributesText',
                'addInputAttributes',
                'inputAttributesText',
                'addContainerAttributes',
                'containerAttributesText',
                'addLeadFieldList',
                'addSaveResult',
                'addIsRequired'
            );
            foreach ($addFields as $f) {
                if (isset($customParams['builderOptions'][$f])) {
                    $$f = (boolean) $customParams['builderOptions'][$f];
                }
            }
        } else {
            $type = $options['data']['type'];
            switch ($type) {
                case 'freetext':
                    $addHelpMessage      = $addDefaultValue = $addIsRequired = $addLeadFieldList = $addSaveResult = false;
                    $labelText           = 'mautic.form.field.form.header';
                    $showLabelText       = 'mautic.form.field.form.showheader';
                    $inputAttributesText = 'mautic.form.field.form.freetext_attributes';
                    $labelAttributesText = 'mautic.form.field.form.header_attributes';

                    // Allow html
                    $cleanMasks['properties'] = 'html';
                    break;
                case 'button':
                    $addHelpMessage = $addShowLabel = $addDefaultValue = $addLabelAttributes = $addIsRequired = $addLeadFieldList = $addSaveResult = false;
                    break;
                case 'hidden':
                    $addHelpMessage = $addShowLabel = $addLabelAttributes = $addIsRequired = false;
                    break;
                case 'captcha':
                    $addShowLabel = $addIsRequired = $addDefaultValue = $addLeadFieldList = $addSaveResult = false;
                    break;
            }
        }

        // Build form fields
        $builder->add(
            'label',
            'text',
            array(
                'label'       => !empty($labelText) ? $labelText : 'mautic.form.field.form.label',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array('class' => 'form-control'),
                'constraints' => array(
                    new Assert\NotBlank(
                        array('message' => 'mautic.form.field.label.notblank')
                    )
                )
            )
        );

        if (!empty($options['customParameters'])) {
            $builder->add('properties', $customParams['formType'], $formTypeOptions);
        } else {
            switch ($type) {
                case 'select':
                case 'country':
                    $builder->add(
                        'properties',
                        'formfield_select',
                        array(
                            'field_type' => $type,
                            'label'      => false,
                            'parentData' => $options['data']
                        )
                    );
                    break;
                case 'checkboxgrp':
                case 'radiogrp':
                    $builder->add(
                        'properties',
                        'formfield_group',
                        array(
                            'label' => false,
                            'data'  => (isset($options['data']['properties'])) ? $options['data']['properties'] : array()
                        )
                    );
                    break;
                case 'freetext':
                    $builder->add(
                        'properties',
                        'formfield_text',
                        array(
                            'required' => false,
                            'label'    => false,
                            'editor'   => true
                        )
                    );
                    break;
                case 'date':
                case 'email':
                case 'number':
                case 'tel':
                case 'text':
                case 'url':
                    $builder->add(
                        'properties',
                        'formfield_placeholder',
                        array(
                            'label' => false
                        )
                    );
                    break;
                case 'captcha':
                    $builder->add(
                        'properties',
                        'formfield_captcha',
                        array(
                            'label' => false
                        )
                    );
                    break;
            }
        }

        if ($addShowLabel) {
            $default = (!isset($options['data']['showLabel'])) ? true : (boolean) $options['data']['showLabel'];
            $builder->add(
                'showLabel',
                'yesno_button_group',
                array(
                    'label' => (!empty($showLabelText)) ? $showLabelText : 'mautic.form.field.form.showlabel',
                    'data'  => $default
                )
            );
        }

        if ($addDefaultValue) {
            $builder->add(
                'defaultValue',
                ($type == 'textarea') ? 'textarea' : 'text',
                array(
                    'label'      => 'mautic.core.defaultvalue',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'required'   => false
                )
            );
        }

        if ($addHelpMessage) {
            $builder->add(
                'helpMessage',
                'text',
                array(
                    'label'      => 'mautic.form.field.form.helpmessage',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.helpmessage'
                    ),
                    'required'   => false
                )
            );
        }

        if ($addIsRequired) {
            $default = (!isset($options['data']['isRequired'])) ? false : (boolean) $options['data']['isRequired'];
            $builder->add(
                'isRequired',
                'yesno_button_group',
                array(
                    'label' => 'mautic.core.required',
                    'data'  => $default
                )
            );

            $builder->add(
                'validationMessage',
                'text',
                array(
                    'label'      => 'mautic.form.field.form.validationmsg',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'required'   => false
                )
            );
        }

        if ($addLabelAttributes) {
            $builder->add(
                'labelAttributes',
                'text',
                array(
                    'label'      => (!empty($labelAttributesText)) ? $labelAttributesText : 'mautic.form.field.form.labelattr',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.attr',
                        'maxlength' => '255'
                    ),
                    'required'   => false
                )
            );
        }

        if ($addInputAttributes) {
            $builder->add(
                'inputAttributes',
                'text',
                array(
                    'label'      => (!empty($inputAttributesText)) ? $inputAttributesText : 'mautic.form.field.form.inputattr',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.attr',
                        'maxlength' => '255'
                    ),
                    'required'   => false
                )
            );
        }

        if ($addContainerAttributes) {
            $builder->add(
                'containerAttributes',
                'text',
                array(
                    'label'      => (!empty($containerAttributesText)) ? $containerAttributesText : 'mautic.form.field.form.container_attr',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.container_attr',
                        'maxlength' => '255'
                    ),
                    'required'   => false
                )
            );
        }

        if ($addSaveResult) {
            $default = (!isset($options['data']['saveResult']) || $options['data']['saveResult'] === null) ? true
                : (boolean) $options['data']['saveResult'];
            $builder->add(
                'saveResult',
                'yesno_button_group',
                array(
                    'label' => 'mautic.form.field.form.saveresult',
                    'data'  => $default,
                    'attr'  => array(
                        'tooltip' => 'mautic.form.field.help.saveresult'
                    )
                )
            );
        }

        if ($addLeadFieldList) {

            if (!isset($options['data']['leadField'])) {
                switch ($type) {
                    case 'email':
                        $data = 'email';
                        break;
                    case 'country':
                        $data = 'country';
                        break;
                    case 'tel':
                        $data = 'phone';
                        break;
                    default:
                        $data = '';
                        break;
                }

            } elseif (isset($options['data']['leadField'])) {
                $data = $options['data']['leadField'];
            } else {
                $data = '';
            }

            $builder->add(
                'leadField',
                'choice',
                array(
                    'choices'    => $options['leadFields'],
                    'label'      => 'mautic.form.field.form.lead_field',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.lead_field'
                    ),
                    'required'   => false,
                    'data'       => $data
                )
            );
        }

        $builder->add('type', 'hidden');

        $update = (!empty($options['data']['id'])) ? true : false;
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add(
            'buttons',
            'form_buttons',
            array(
                'save_text'       => $btnValue,
                'save_icon'       => $btnIcon,
                'apply_text'      => false,
                'container_class' => 'bottom-form-buttons'
            )
        );

        $builder->add(
            'formId',
            'hidden',
            array(
                'mapped' => false
            )
        );

        $builder->addEventSubscriber(new CleanFormSubscriber($cleanMasks));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'customParameters' => false
            )
        );

        $resolver->setOptional(array('customParameters'));

        $resolver->setRequired(array('leadFields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formfield";
    }
}
