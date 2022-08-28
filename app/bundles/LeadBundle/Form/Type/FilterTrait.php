<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Entity\RegexTrait;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait FilterTrait
{
    use RegexTrait;

    /**
     * @var Connection
     */
    protected $connection;

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $eventName
     */
    public function buildFiltersForm($eventName, FormEvent $event, TranslatorInterface $translator, $currentListId = null)
    {
        $data    = $event->getData();
        $form    = $event->getForm();
        $options = $form->getConfig()->getOptions();

        if (!isset($data['type'])) {
            $data['type']     = TextType::class;
            $data['field']    = '';
            $data['operator'] = null;
        }

        $fieldType   = $data['type'];
        $fieldName   = $data['field'];
        $type        = TextType::class;
        $attr        = ['class' => 'form-control filter-value'];
        $displayType = HiddenType::class;
        $displayAttr = [];
        $operator    = isset($data['operator']) ? $data['operator'] : '';
        $field       = [];

        if (isset($options['fields']['behaviors'][$fieldName])) {
            $field = $options['fields']['behaviors'][$fieldName];
        } elseif (isset($data['object']) && isset($options['fields'][$data['object']][$fieldName])) {
            $field = $options['fields'][$data['object']][$fieldName];
        }

        $customOptions = [];
        switch ($fieldType) {
            case 'assets':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']                   = $options['assets'];
                $customOptions['multiple']                  = true;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                break;
            case 'leadlist':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                // Don't show the current list ID in the choices
                if (!empty($currentListId)) {
                    unset($options['lists'][$currentListId]);
                }

                $customOptions['choices']                   = $options['lists'];
                $customOptions['multiple']                  = true;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                break;
            case 'campaign':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']                   = $options['campaign'];
                $customOptions['multiple']                  = true;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                break;
            case 'lead_email_received':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']                   = $options['emails'];
                $customOptions['multiple']                  = true;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                break;
            case 'device_type':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']           = $options['deviceTypes'];
                $customOptions['multiple']          = true;
                $type                               = ChoiceType::class;
                break;
            case 'device_brand':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']           = $options['deviceBrands'];
                $customOptions['multiple']          = true;
                $type                               = ChoiceType::class;
                break;
            case 'device_os':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }

                $customOptions['choices']           = $options['deviceOs'];
                $customOptions['multiple']          = true;
                $type                               = ChoiceType::class;
                break;
            case 'tags':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }
                $customOptions['choices']                   = $options['tags'];
                $customOptions['multiple']                  = true;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                $attr                                       = array_merge(
                    $attr,
                    [
                        'data-placeholder'     => $translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ]
                );
                break;
            case 'stage':
                $customOptions['choices']                   = $options['stage'];
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
                break;
            case 'globalcategory':
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }
                $customOptions['choices']           = $options['globalcategory'];
                $customOptions['multiple']          = true;
                $type                               = ChoiceType::class;
                break;
            case 'timezone':
            case 'country':
            case 'region':
            case 'locale':
                switch ($fieldType) {
                    case 'timezone':
                        $choiceKey = 'timezones';
                        break;
                    case 'country':
                        $choiceKey = 'countries';
                        break;
                    case 'region':
                        $choiceKey = 'regions';
                        break;
                    case 'locale':
                        $choiceKey = 'locales';
                        break;
                }

                $type                                       = ChoiceType::class;
                $customOptions['choices']                   = $options[$choiceKey];
                $customOptions['choice_translation_domain'] = false;
                $customOptions['multiple']                  = (in_array($operator, ['in', '!in']));

                if ($customOptions['multiple']) {
                    array_unshift($customOptions['choices'], ['' => '']);

                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    }
                }

                break;
            case 'time':
            case 'date':
            case 'datetime':
                $attr['data-toggle'] = $fieldType;
                break;
            case 'lookup_id':
                $type        = HiddenType::class;
                $displayType = TextType::class;
                $displayAttr = array_merge(
                    $displayAttr,
                    [
                        'class'                => 'form-control',
                        'data-toggle'          => 'field-lookup',
                        'data-target'          => $data['field'],
                        'data-action'          => isset($field['properties']['data-action']) ? $field['properties']['data-action'] : 'lead:fieldList',
                        'data-lookup-callback' => isset($field['properties']['data-lookup-callback']) ? $field['properties']['data-lookup-callback'] : 'updateLookupListFilter',
                        'data-callback'        => isset($field['properties']['callback']) ? $field['properties']['callback'] : 'activateFieldTypeahead',
                        'placeholder'          => $translator->trans(
                            'mautic.lead.list.form.filtervalue'
                        ),
                    ]
                );

                if (isset($field['properties']['list'])) {
                    $displayAttr['data-options'] = $field['properties']['list'];
                }

                break;
            case 'select':
            case 'multiselect':
            case 'boolean':
                $attr = array_merge(
                    $attr,
                    [
                        'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue'),
                    ]
                );

                if (in_array($operator, ['in', '!in'])) {
                    $customOptions['multiple'] = true;
                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    } elseif (!is_array($data['filter'])) {
                        $data['filter'] = [$data['filter']];
                    }
                }

                $choices = [];
                if (!empty($field['properties']['list'])) {
                    $list    = $field['properties']['list'];
                    $choices =
                        ArrayHelper::flipArray(
                            ('boolean' === $fieldType)
                                ?
                                FormFieldHelper::parseBooleanList($list)
                                :
                                FormFieldHelper::parseList($list)
                        );
                }

                if ('select' === $fieldType) {
                    // array_unshift cannot be used because numeric values get lost as keys
                    $choices     = array_reverse($choices, true);
                    $choices[''] = '';
                    $choices     = array_reverse($choices, true);
                }

                $customOptions['choices']                   = $choices;
                $customOptions['choice_translation_domain'] = false;
                $type                                       = ChoiceType::class;
            break;
            case 'lookup':
                if ('number' !== $fieldType) {
                    $attr = array_merge(
                        $attr,
                        [
                            'data-toggle' => 'field-lookup',
                            'data-target' => isset($data['field']) ? $data['field'] : '',
                            'data-action' => 'lead:fieldList',
                            'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue'),
                        ]
                    );

                    if (isset($field['properties']['list'])) {
                        $attr['data-options'] = $field['properties']['list'];
                    }
                }

                break;
        }

        $customOptions['constraints'] = [];
        if (in_array($operator, ['empty', '!empty'])) {
            $attr['disabled'] = 'disabled';
        } elseif ($operator) {
            $customOptions['constraints'][] = new NotBlank(
                [
                    'message' => 'mautic.core.value.required',
                ]
            );

            if (in_array($operator, ['regexp', '!regexp']) && $this->connection) {
                // Let's add a custom valdiator to test the regex
                $customOptions['constraints'][] =
                    new Callback(
                        function ($regex, ExecutionContextInterface $context) {
                            // Let's test the regex's syntax by making a fake query
                            try {
                                $qb = $this->connection->createQueryBuilder();
                                $qb->select('l.id')
                                    ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                                    ->where('l.id REGEXP :regex')
                                    ->setParameter('regex', $this->prepareRegex($regex))
                                    ->setMaxResults(1);
                                $qb->execute()->fetchAll();
                            } catch (\Exception $exception) {
                                $context->buildViolation('mautic.core.regex.invalid')->addViolation();
                            }
                        }
                    );
            }
        }

        // @todo implement in UI
        if (in_array($operator, ['between', '!between'])) {
            $form->add(
                'filter',
                CollectionType::class,
                [
                    'label'         => false,
                    'entry_type'    => $type,
                    'entry_options' => [
                        'label' => false,
                        'attr'  => $attr,
                    ],
                ]
            );
        } else {
            if (!empty($customOptions['constraints'])) {
                foreach ($customOptions['constraints'] as $i => $constraint) {
                    if ('NotBlank' === get_class($constraint)) {
                        array_splice($customOptions['constraints'], $i, 1);
                    }
                }
            }
            $form->add(
                'filter',
                $type,
                array_merge(
                    [
                        'label'          => false,
                        'attr'           => $attr,
                        'data'           => $data['filter'] ?? '',
                        'error_bubbling' => false,
                    ],
                    $customOptions
                )
            );
        }

        $form->add(
            'display',
            $displayType,
            [
                'label'          => false,
                'attr'           => $displayAttr,
                'data'           => $data['display'] ?? '',
                'error_bubbling' => false,
            ]
        );

        $form->add(
            'operator',
            ChoiceType::class,
            [
                'label'   => false,
                'choices' => $field['operators'] ?? [],
                'attr'    => [
                    'class'    => 'form-control not-chosen filter-operator',
                    'onchange' => 'Mautic.convertDwcFilterInput(this)',
                ],
            ]
        );

        if (FormEvents::PRE_SUBMIT == $eventName) {
            $event->setData($data);
        }
    }
}
