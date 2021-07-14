<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentFiltersSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * SegmentFiltersSubscriber constructor.
     */
    public function __construct(TranslatorInterface $translator, ListModel $listModel)
    {
        $this->translator = $translator;
        $this->listModel  = $listModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['filtersChoicesGenerate', 0],
        ];
    }

    public function filtersChoicesGenerate(LeadListFiltersChoicesEvent $event)
    {
        if (0 === strpos($event->getRoute(), 'mautic_segment_action')) {
            foreach ($this->getSegmentFilters() as $choiceKey => $segmentFilter) {
                $event->addChoice('lead', $choiceKey, $segmentFilter);
            }
        }
    }

    private function getSegmentFilters()
    {
        //field choices
        $choices =  [
            'date_added' => [
                'label'      => $this->translator->trans('mautic.core.date.added'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_identified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_identified'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'last_active' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.last_active'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_modified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_modified'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'owner_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.owner'),
                'properties' => [
                    'type'     => 'lookup_id',
                    'callback' => 'activateSegmentFilterTypeahead',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('lookup_id'),
                'object'    => 'lead',
            ],
            'points' => [
                'label'      => $this->translator->trans('mautic.lead.lead.event.points'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'leadlist' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lists'),
                'properties' => [
                    'type' => 'leadlist',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.campaign'),
                'properties' => [
                    'type' => 'campaign',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'lead_asset_download' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_asset_download'),
                'properties' => ['type' => 'assets'],
                'operators'  => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ],
            'lead_email_received' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_received'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent'),
                'properties' => [
                    'type' => 'lead_email_received',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => [
                            'in',
                            '!in',
                        ],
                    ]
                ),
                'object' => 'lead',
            ],
            'lead_email_sent_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
            'lead_email_read_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.tags'),
                'properties' => [
                    'type' => 'tags',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_type' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_type'),
                'properties' => [
                    'type' => 'device_type',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_brand' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_brand'),
                'properties' => [
                    'type' => 'device_brand',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_os' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_os'),
                'properties' => [
                    'type' => 'device_os',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'device_model' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_model'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_manual_email' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_manual_email'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_bounced_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'hit_url' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.session'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.referer'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.url_title'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.source'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
            'source_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.source.id'),
                'properties' => [
                    'type' => 'number',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'notification' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.notification'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'page_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.page_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'email_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.email_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'redirect_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.redirect_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        0 => $this->translator->trans('mautic.core.form.no'),
                        1 => $this->translator->trans('mautic.core.form.yes'),
                    ],
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'stage' => [
                'label'      => $this->translator->trans('mautic.lead.lead.field.stage'),
                'properties' => [
                    'type' => 'stage',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType(
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
                'label'      => $this->translator->trans('mautic.lead.list.filter.categories'),
                'properties' => [
                    'type' => 'globalcategory',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('multiselect'),
                'object'    => 'lead',
            ],
            'utm_campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcampaign'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_content' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcontent'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_medium' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmmedium'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_source' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmsource'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
            'utm_term' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmterm'),
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => $this->listModel->getOperatorsForFieldType('default'),
                'object'    => 'lead',
            ],
        ];

        return $choices;
    }
}
