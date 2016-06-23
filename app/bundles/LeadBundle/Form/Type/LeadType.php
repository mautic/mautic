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
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\DataTransformer\StringToDatetimeTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class LeadType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class LeadType extends AbstractType
{

    private $translator;
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('lead.lead', $options));

        if (!$options['isShortForm']) {
            $imageChoices = array(
                'gravatar' => 'Gravatar',
                'custom'   => 'mautic.lead.lead.field.custom_avatar'
            );

            $cache = $options['data']->getSocialCache();
            if (count($cache)) {
                foreach ($cache as $key => $data) {
                    $imageChoices[$key] = $key;
                }
            }

            $builder->add(
                'preferred_profile_image',
                'choice',
                array(
                    'choices'    => $imageChoices,
                    'label'      => 'mautic.lead.lead.field.preferred_profile',
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => true,
                    'multiple'   => false,
                    'attr'       => array(
                        'class' => 'form-control'
                    )
                )
            );

            $builder->add(
                'custom_avatar',
                'file',
                array(
                    'label'      => false,
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => false,
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'mapped'     => false,
                    'constraints' => array(
                        new File(
                            array(
                                'mimeTypes' => array(
                                    'image/gif',
                                    'image/jpeg',
                                    'image/png'
                                ),
                                'mimeTypesMessage' => 'mautic.lead.avatar.types_invalid'
                            )
                        )
                    )
                )
            );
        }

        $fieldValues = (!empty($options['data'])) ? $options['data']->getFields() : array('filter' => array('isVisible' => true));
        foreach ($options['fields'] as $field) {
            if ($field['isPublished'] === false) continue;
            $attr        = array('class' => 'form-control');
            $properties  = $field['properties'];
            $type        = $field['type'];
            $required    = $field['isRequired'];
            $alias       = $field['alias'];
            $group       = $field['group'];
            $value       = (isset($fieldValues[$group][$alias]['value'])) ?
                $fieldValues[$group][$alias]['value'] : $field['defaultValue'];
            $constraints = array();
            if ($required) {
                $constraints[] = new NotBlank(
                    array('message' => 'mautic.lead.customfield.notblank')
                );
            }
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
                        'required'      => $required,
                        'label'         => $field['label'],
                        'label_attr'    => array('class' => 'control-label'),
                        'attr'          => $attr,
                        'data'          => (isset($fieldValues[$group][$alias]['value'])) ?
                            (float) $fieldValues[$group][$alias]['value'] : (float) $field['defaultValue'],
                        'mapped'        => false,
                        'constraints'   => $constraints,
                        'precision'     => $properties['precision'],
                        'rounding_mode' => (int) $properties['roundmode']
                    )
                );
            } elseif (in_array($type, array('date', 'datetime', 'time'))) {
                $attr['data-toggle'] = $type;
                $opts = array(
                    'required'    => $required,
                    'label'       => $field['label'],
                    'label_attr'  => array('class' => 'control-label'),
                    'widget'      => 'single_text',
                    'attr'        => $attr,
                    'mapped'      => false,
                    'input'       => 'string',
                    'html5'       => false,
                    'constraints' => $constraints
                );

                try {
                    $dtHelper = new DateTimeHelper($value, null, 'local');
                } catch (\Exception $e) {
                    // Rather return empty value than break the page
                    $value = '';
                }

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
                            'required'    => $required,
                            'label'       => $field['label'],
                            'label_attr'  => array('class' => 'control-label'),
                            'data'        => ($type == 'boolean') ? (int) $value : $value,
                            'attr'        => $attr,
                            'mapped'      => false,
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
                        'required'    => $required,
                        'label'       => $field['label'],
                        'label_attr'  => array('class' => 'control-label'),
                        'data'        => $value,
                        'attr'        => array(
                            'class'            => 'form-control',
                            'data-placeholder' => $field['label']
                        ),
                        'mapped'      => false,
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
                        'required'    => $field['isRequired'],
                        'label'       => $field['label'],
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => $attr,
                        'data'        => $value,
                        'mapped'      => false,
                        'constraints' => $constraints
                    )
                );
            }
        }

        $builder->add(
            'tags',
            'lead_tag',
            array(
                'by_reference' => false,
                'attr'         => array(
                    'data-placeholder'      => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text'  => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'        => 'true',
                    'onchange'              => 'Mautic.createLeadTag(this)'
                )
            )
        );

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                array(
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'required'   => false,
                    'multiple'   => false
                )
            )
            ->addModelTransformer($transformer)
        );

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticStageBundle:Stage'
        );

        $builder->add(
            $builder->create(
                'stage',
                'stage_list',
                array(
                    'label'      => 'mautic.lead.lead.field.stage',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'required'   => false,
                    'multiple'   => false
                )
            )
                ->addModelTransformer($transformer)
        );

        if (!$options['isShortForm']) {
            $builder->add('buttons', 'form_buttons');
        } else {
            $builder->add(
                'buttons',
                'form_buttons',
                array(
                    'apply_text' => false,
                    'save_text'  => 'mautic.core.form.save'
                )
            );
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'  => 'Mautic\LeadBundle\Entity\Lead',
                'isShortForm' => false
            )
        );

        $resolver->setRequired(array('fields', 'isShortForm'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead";
    }
}
