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
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;

/**
 * Class UpdateLeadActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class UpdateLeadActionType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->factory->getModel('lead.field');
        $leadFields = $fieldModel->getEntities(
            array(
                'force'          => array(
                    array(
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                ),
                'hydration_mode' => 'HYDRATE_ARRAY'
            )
        );

        foreach ($leadFields as $key => $field) {
            if ($field['isPublished'] === false) continue;
            $attr        = array('class' => 'form-control');
            $properties  = $field['properties'];
            $type        = $field['type'];
            $alias       = $field['alias'];
            $value       = isset($options['data'][$alias]) ? $options['data'][$alias] : '';
            $constraints = array();
            if ($type == 'number') {
                if (empty($properties['precision'])) {
                    $properties['precision'] = null;
                } //ensure default locale is used
                else {
                    $properties['precision'] = (int) $properties['precision'];
                }

                $builder->add(
                    $alias,
                    $type,
                    array(
                        'required'      => false,
                        'label'         => $field['label'],
                        'label_attr'    => array('class' => 'control-label'),
                        'attr'          => $attr,
                        'data'          => (empty($value)) ? null : $value, // prevent error that it's not a number
                        'constraints'   => $constraints,
                        'precision'     => $properties['precision'],
                        'rounding_mode' => (int) $properties['roundmode']
                    )
                );
            } elseif (in_array($type, array('date', 'datetime', 'time'))) {
                $attr['data-toggle'] = $type;
                $opts = array(
                    'required'    => false,
                    'label'       => $field['label'],
                    'label_attr'  => array('class' => 'control-label'),
                    'widget'      => 'single_text',
                    'attr'        => $attr,
                    'input'       => 'string',
                    'html5'       => false,
                    'data'        => $value,
                    'constraints' => $constraints
                );

                $dtHelper = new DateTimeHelper($value, null, 'local');

                if ($type == 'datetime') {
                    $opts['model_timezone'] = 'UTC';
                    $opts['view_timezone']  = date_default_timezone_get();
                    $opts['format']         = 'yyyy-MM-dd HH:mm';
                    $opts['with_seconds']   = false;

                    $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d H:i:s') : null;
                } elseif ($type == 'date') {
                    $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d') : null;
                } else {
                    $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('H:i:s') : null;
                }

                $builder->add($alias, $type, $opts);
            } elseif ($type == 'select' || $type == 'boolean') {
                $choices = array();
                if ($type == 'select' && !empty($properties['list'])) {
                    $list = explode('|', $properties['list']);
                    foreach ($list as $l) {
                        $l           = trim($l);
                        $choices[$l] = $l;
                    }
                    $expanded = false;
                }
                if ($type == 'boolean' && !empty($properties['yes']) && !empty($properties['no'])) {
                    $expanded = true;
                    $choices  = array(1 => $properties['yes'], 0 => $properties['no']);
                    $attr     = array();
                }

                if (!empty($choices)) {
                    $builder->add(
                        $alias,
                        'choice',
                        array(
                            'choices'     => $choices,
                            'required'    => false,
                            'label'       => $field['label'],
                            'label_attr'  => array('class' => 'control-label'),
                            'data'        => ($type == 'boolean') ? (int) $value : $value,
                            'attr'        => $attr,
                            'multiple'    => false,
                            'empty_value' => false,
                            'expanded'    => $expanded,
                            'constraints' => $constraints
                        )
                    );
                }
            } elseif ($type == 'country' || $type == 'region' || $type == 'timezone') {
                if ($type == 'country') {
                    $choices = FormFieldHelper::getCountryChoices();
                } elseif ($type == 'region') {
                    $choices = FormFieldHelper::getRegionChoices();
                } else {
                    $choices = FormFieldHelper::getTimezonesChoices();
                }

                $builder->add(
                    $alias,
                    'choice',
                    array(
                        'choices'     => $choices,
                        'required'    => false,
                        'label'       => $field['label'],
                        'label_attr'  => array('class' => 'control-label'),
                        'data'        => $value,
                        'attr'        => array(
                            'class'            => 'form-control',
                            'data-placeholder' => $field['label']
                        ),
                        'multiple'    => false,
                        'expanded'    => false,
                        'constraints' => $constraints
                    )
                );
            } else {
                if ($type == 'lookup') {
                    $type                = "text";
                    $attr['data-toggle'] = 'field-lookup';
                    $attr['data-target'] = $alias;

                    if (!empty($properties['list'])) {
                        $attr['data-options'] = $properties['list'];
                    }
                }
                $builder->add(
                    $alias,
                    $type,
                    array(
                        'required'    => false,
                        'label'       => $field['label'],
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => $attr,
                        'data'        => $value,
                        'constraints' => $constraints
                    )
                );
            }
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "updatelead_action";
    }
}