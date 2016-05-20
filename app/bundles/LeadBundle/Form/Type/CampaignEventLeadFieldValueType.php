<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class CampaignEventLeadFieldValueType
 */
class CampaignEventLeadFieldValueType extends AbstractType
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('field', 'leadfields_choices', array(
            'label'         => 'mautic.lead.campaign.event.field',
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'empty_value'   => 'mautic.core.select',
            'attr'          => array(
                'class'     => 'form-control',
                'tooltip'   => 'mautic.lead.campaign.event.field_descr',
                'onchange'  => 'Mautic.updateLeadFieldValues(this)'
            )
        ));

        $leadModel   = $this->factory->getModel('lead.lead');
        $fieldModel  = $this->factory->getModel('lead.field');
        $operators   = $leadModel->getFilterExpressionFunctions();
        $choices     = array();


        foreach ($operators as $key => $operator) {
            $choices[$key] = $operator['label'];
        }

        $builder->add('operator', 'choice', array(
            'choices'  => $choices,
        ));

        $ff = $builder->getFormFactory();

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) use ($ff, $fieldModel) {
            $data    = $e->getData();
            $form    = $e->getForm();

            $fieldValues = null;
            $fieldType = null;
            $choiceTypes = array('boolean', 'country', 'region', 'lookup', 'timezone', 'select', 'radio');

            if (isset($data['field'])) {
                $field = $fieldModel->getRepository()->findOneBy(array('alias' => $data['field']));
                
                if ($field) {
                    $properties = $field->getProperties();
                    $fieldType = $field->getType();
                    if (!empty($properties['list'])) {
                        // Lookup/Select options
                        $fieldValues = explode('|', $properties['list']);
                        $fieldValues = array_combine($fieldValues, $fieldValues);
                    } elseif (!empty($properties) && $fieldType == 'boolean') {
                        // Boolean options
                        $fieldValues = array(
                            0 => $properties['no'],
                            1 => $properties['yes']
                        );
                    } elseif (!empty($properties)) {
                        // fallback
                        $fieldValues = $properties;
                    }
                }
            }


            // Display selectbox for a field with choices, textbox for others
            if (!empty($fieldValues) && in_array($fieldType, $choiceTypes)) {
                $form->add('value', 'choice', array(
                    'choices'    => $fieldValues,
                    'label'      => 'mautic.form.field.form.value',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control not-chosen'
                    )
                ));
            } else {
                $form->add('value', 'text', array(
                    'label'      => 'mautic.form.field.form.value',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control'
                    )
                ));
            }

        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_BIND, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "campaignevent_lead_field_value";
    }
}
