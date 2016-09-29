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
use Mautic\LeadBundle\Form\Type\EntityFieldsBuildFormTrait;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\DataTransformer\StringToDatetimeTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
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
    use EntityFieldsBuildFormTrait;

    private $translator;
    private $factory;
    private $companyModel;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, CompanyModel $companyModel)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
        $this->companyModel = $companyModel;
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
            $imageChoices = [
                'gravatar' => 'Gravatar',
                'custom'   => 'mautic.lead.lead.field.custom_avatar'
            ];

            $cache = $options['data']->getSocialCache();
            if (count($cache)) {
                foreach ($cache as $key => $data) {
                    $imageChoices[$key] = $key;
                }
            }

            $builder->add(
                'preferred_profile_image',
                'choice',
                [
                    'choices'    => $imageChoices,
                    'label'      => 'mautic.lead.lead.field.preferred_profile',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => true,
                    'multiple'   => false,
                    'attr'       => [
                        'class' => 'form-control'
                    ]
                ]
            );

            $builder->add(
                'custom_avatar',
                'file',
                [
                    'label'      => false,
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class' => 'form-control'
                    ],
                    'mapped'     => false,
                    'constraints' => [
                        new File(
                            [
                                'mimeTypes' => [
                                    'image/gif',
                                    'image/jpeg',
                                    'image/png'
                                ],
                                'mimeTypesMessage' => 'mautic.lead.avatar.types_invalid'
                            ]
                        )
                    ]
                ]
            );
        }

        $fieldValues = (!empty($options['data'])) ? $options['data']->getFields() : ['filter' => ['isVisible' => true]];
        foreach ($options['fields'] as $field) {
            if ($field['isPublished'] === false) continue;
            $attr        = ['class' => 'form-control'];
            $properties  = $field['properties'];
            $type        = $field['type'];
            $required    = $field['isRequired'];
            $alias       = $field['alias'];
            $group       = $field['group'];

            if ($field['isUniqueIdentifer']) {
                $attr['data-unique-identifier'] = $field['alias'];
            }

            $value       = (isset($fieldValues[$group][$alias]['value'])) ?
                $fieldValues[$group][$alias]['value'] : $field['defaultValue'];
            $constraints = [];
            if ($required) {
                $constraints[] = new NotBlank(
                    ['message' => 'mautic.lead.customfield.notblank']
                );
            }

            switch ($type) {
                case 'number':
                    if (empty($properties['precision'])) {
                        $properties['precision'] = null;
                    } //ensure default locale is used
                    else {
                        $properties['precision'] = (int) $properties['precision'];
                    }

                    $builder->add(
                        $alias,
                        $type,
                        [
                            'required'      => $required,
                            'label'         => $field['label'],
                            'label_attr'    => ['class' => 'control-label'],
                            'attr'          => $attr,
                            'data'          => (isset($fieldValues[$group][$alias]['value'])) ?
                                (float) $fieldValues[$group][$alias]['value'] : (float) $field['defaultValue'],
                            'mapped'        => false,
                            'constraints'   => $constraints,
                            'precision'     => $properties['precision'],
                            'rounding_mode' => isset($properties['roundmode']) ? (int) $properties['roundmode'] : 0
                        ]
                    );
                    break;
                case 'date':
                case 'datetime':
                case 'time':
                    $attr['data-toggle'] = $type;
                    $opts                = [
                        'required'    => $required,
                        'label'       => $field['label'],
                        'label_attr'  => ['class' => 'control-label'],
                        'widget'      => 'single_text',
                        'attr'        => $attr,
                        'mapped'      => false,
                        'input'       => 'string',
                        'html5'       => false,
                        'constraints' => $constraints
                    ];

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
                    break;
                case 'select':
                case 'boolean':
                    $choices = [];
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
                        $choices  = [1 => $properties['yes'], 0 => $properties['no']];
                        $attr     = [];
                    }

                    if (!empty($choices)) {
                        $builder->add(
                            $alias,
                            'choice',
                            [
                                'choices'     => $choices,
                                'required'    => $required,
                                'label'       => $field['label'],
                                'label_attr'  => ['class' => 'control-label'],
                                'data'        => ($type == 'boolean') ? (int) $value : $value,
                                'attr'        => $attr,
                                'mapped'      => false,
                                'multiple'    => false,
                                'empty_value' => false,
                                'expanded'    => $expanded,
                                'constraints' => $constraints
                            ]
                        );
                    }
                    break;
                case 'country':
                case 'region':
                case 'timezone':
                case 'locale':
                    switch ($type) {
                        case 'country':
                            $choices = FormFieldHelper::getCountryChoices();
                            break;
                        case 'region':
                            $choices = FormFieldHelper::getRegionChoices();
                            break;
                        case 'timezone':
                            $choices = FormFieldHelper::getTimezonesChoices();
                            break;
                        case 'locale':
                            $choices = FormFieldHelper::getLocaleChoices();
                            break;
                    }

                    $builder->add(
                        $alias,
                        'choice',
                        [
                            'choices'     => $choices,
                            'required'    => $required,
                            'label'       => $field['label'],
                            'label_attr'  => ['class' => 'control-label'],
                            'data'        => $value,
                            'attr'        => [
                                'class'            => 'form-control',
                                'data-placeholder' => $field['label']
                            ],
                            'mapped'      => false,
                            'multiple'    => false,
                            'expanded'    => false,
                            'constraints' => $constraints
                        ]
                    );
                    break;
                default:
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
                        [
                            'required'    => $field['isRequired'],
                            'label'       => $field['label'],
                            'label_attr'  => ['class' => 'control-label'],
                            'attr'        => $attr,
                            'data'        => $value,
                            'mapped'      => false,
                            'constraints' => $constraints
                        ]
                    );
                    break;
            }
        }

        $builder->add(
            'tags',
            'lead_tag',
            [
                'by_reference' => false,
                'attr'         => [
                    'data-placeholder'      => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text'  => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'        => 'true',
                    'onchange'              => 'Mautic.createLeadTag(this)'
                ]
            ]
        );

        $companyLeadRepo = $this->companyModel->getCompanyLeadRepository();
        $companies = $companyLeadRepo->getCompaniesByLeadId($options['data']->getId());
        $leadCompanies = [];
        foreach ($companies as $company) {
            $leadCompanies[$company['company_id']] = $company['company_id'];
        }

        $builder->add(
        'companies',
            'company_list',
            [
                'label'       => 'mautic.company.selectcompany',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                        'class'    => 'form-control',
                        'tooltip'  => 'mautic.company.choose.company_descr',
                     ],
                'multiple'    => true,
                'required'    => false,
                'mapped'     => false,
                'data'      => $leadCompanies
            ]
        );

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                [
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control'
                    ],
                    'required'   => false,
                    'multiple'   => false
                ]
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
                [
                    'label'      => 'mautic.lead.lead.field.stage',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control'
                    ],
                    'required'   => false,
                    'multiple'   => false
                ]
            )
                ->addModelTransformer($transformer)
        );

        if (!$options['isShortForm']) {
            $builder->add('buttons', 'form_buttons');
        } else {
            $builder->add(
                'buttons',
                'form_buttons',
                [
                    'apply_text' => false,
                    'save_text'  => 'mautic.core.form.save'
                ]
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
            [
                'data_class'  => 'Mautic\LeadBundle\Entity\Lead',
                'isShortForm' => false
            ]
        );

        $resolver->setRequired(['fields', 'isShortForm']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead";
    }
}
