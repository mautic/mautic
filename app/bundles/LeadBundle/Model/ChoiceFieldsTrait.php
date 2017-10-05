<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Trait ChoiceFieldsTrait
 */
trait ChoiceFieldsTrait
{
    /**
     * Get a list of field choices for filters.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getChoiceFields()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->translator;

        //field choices
        $choices['lead'] = [
            'date_added' => [
                'label'      => $translator->trans('mautic.core.date.added'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_identified' => [
                'label'      => $translator->trans('mautic.lead.list.filter.date_identified'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'last_active' => [
                'label'      => $translator->trans('mautic.lead.list.filter.last_active'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_modified' => [
                'label'      => $translator->trans('mautic.lead.list.filter.date_modified'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'owner_id' => [
                'label'      => $translator->trans('mautic.lead.list.filter.owner'),
                'properties' => [
                    'type'     => 'lookup_id',
                    'callback' => 'activateSegmentFilterTypeahead',
                ],
                'operators' => $this->getOperatorsForFieldType('text'),
                'object'    => 'lead',
            ],
            'points' => [
                'label'      => $translator->trans('mautic.lead.lead.event.points'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'leadlist' => [
                'label'      => $translator->trans('mautic.lead.list.filter.lists'),
                'properties' => [
                    'type' => 'leadlist',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'lead_email_received' => [
                'label'      => $translator->trans('mautic.lead.list.filter.lead_email_received'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            'in',
                            '!in',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_sent' => [
                'label'      => $translator->trans('mautic.lead.list.filter.lead_email_sent'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            'in',
                            '!in',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_read_date' => [
                'label'      => $translator->trans('mautic.lead.list.filter.lead_email_read_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'lt',
                            'gte',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_read_count' => [
                'label'      => $translator->trans('mautic.lead.list.filter.lead_email_read_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'tags' => [
                'label'      => $translator->trans('mautic.lead.list.filter.tags'),
                'properties' => [
                    'type' => 'tags',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_type' => [
                'label'      => $translator->trans('mautic.lead.list.filter.device_type'),
                'properties' => [
                    'type' => 'device_type',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_brand' => [
                'label'      => $translator->trans('mautic.lead.list.filter.device_brand'),
                'properties' => [
                    'type' => 'device_brand',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_os' => [
                'label'      => $translator->trans('mautic.lead.list.filter.device_os'),
                'properties' => [
                    'type' => 'device_os',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_model' => [
                'label'      => $translator->trans('mautic.lead.list.filter.device_model'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'like',
                            'regexp',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'dnc_bounced' => [
                'label'      => $translator->trans('mautic.lead.list.filter.dnc_bounced'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed' => [
                'label'      => $translator->trans('mautic.lead.list.filter.dnc_unsubscribed'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_bounced_sms' => [
                'label'      => $translator->trans('mautic.lead.list.filter.dnc_bounced_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed_sms' => [
                'label'      => $translator->trans('mautic.lead.list.filter.dnc_unsubscribed_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'hit_url' => [
                'label'      => $translator->trans('mautic.lead.list.filter.visited_url'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'hit_url_date' => [
                'label'      => $translator->trans('mautic.lead.list.filter.visited_url_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'lt',
                            'gte',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'hit_url_count' => [
                'label'      => $translator->trans('mautic.lead.list.filter.visited_url_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'sessions' => [
                'label'      => $translator->trans('mautic.lead.list.filter.session'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'referer' => [
                'label'      => $translator->trans('mautic.lead.list.filter.referer'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'url_title' => [
                'label'      => $translator->trans('mautic.lead.list.filter.url_title'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'source' => [
                'label'      => $translator->trans('mautic.lead.list.filter.source'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'like',
                            '!like',
                            'regexp',
                            '!regexp',
                            'startsWith',
                            'endsWith',
                            'contains',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'notification' => [
                'label'      => $translator->trans('mautic.lead.list.filter.notification'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'page_id' => [
                'label'      => $translator->trans('mautic.lead.list.filter.page_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'email_id' => [
                'label'      => $translator->trans('mautic.lead.list.filter.email_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'redirect_id' => [
                'label'      => $translator->trans('mautic.lead.list.filter.redirect_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $translator->trans('mautic.core.form.no'),
                        1 => $translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'stage' => [
                'label'      => $translator->trans('mautic.lead.lead.field.stage'),
                'properties' => [
                    'type' => 'stage',
                ],
                'operators' => $this->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'empty',
                            '!empty',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'globalcategory' => [
                'label'      => $translator->trans('mautic.lead.list.filter.categories'),
                'properties' => [
                    'type' => 'globalcategory',
                ],
                'operators' => $this->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
        ];

        // Add custom choices
        if ($this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE)) {
            $event = new LeadListFiltersChoicesEvent($choices, $this->getOperatorsForFieldType(), $translator);
            $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE, $event);
            $choices = $event->getChoices();
        }

        //get list of custom fields
        $fields = $this->em->getRepository('MauticLeadBundle:LeadField')->getEntities(
            [
                'filter' => [
                    'isListable'  => true,
                    'isPublished' => true,
                ],
                'orderBy' => 'f.object',
            ]
        );
        foreach ($fields as $field) {
            $type               = $field->getType();
            $properties         = $field->getProperties();
            $properties['type'] = $type;
            if (in_array($type, ['lookup', 'multiselect', 'boolean'])) {
                if ($type == 'boolean') {
                    //create a lookup list with ID
                    $properties['list'] = [
                        0 => $properties['no'],
                        1 => $properties['yes'],
                    ];
                } else {
                    $properties['callback'] = 'activateLeadFieldTypeahead';
                    $properties['list']     = (isset($properties['list'])) ? FormFieldHelper::formatList(
                        FormFieldHelper::FORMAT_BAR,
                        FormFieldHelper::parseList($properties['list'])
                    ) : '';
                }
            }
            $choices[$field->getObject()][$field->getAlias()] = [
                'label'      => $field->getLabel(),
                'properties' => $properties,
                'object'     => $field->getObject(),
            ];

            $choices[$field->getObject()][$field->getAlias()]['operators'] = $this->getOperatorsForFieldType($type);
        }

        foreach ($choices as $key => $choice) {
            $cmp = function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            };
            uasort($choice, $cmp);
            $choices[$key] = $choice;
        }

        return $choices;
    }
}
