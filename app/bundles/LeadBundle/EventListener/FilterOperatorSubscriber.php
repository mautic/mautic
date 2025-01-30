<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FieldChoicesProviderInterface;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FilterOperatorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OperatorOptions $operatorOptions,
        private LeadFieldRepository $leadFieldRepository,
        private TypeOperatorProviderInterface $typeOperatorProvider,
        private FieldChoicesProviderInterface $fieldChoicesProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => ['onListOperatorsGenerate', 0],
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE   => [
                ['onGenerateSegmentFiltersAddStaticFields', 0],
                ['onGenerateSegmentFiltersAddCustomFields', 0],
                ['onGenerateSegmentFiltersAddBehaviors', 0],
            ],
        ];
    }

    public function onListOperatorsGenerate(LeadListFiltersOperatorsEvent $event): void
    {
        foreach ($this->operatorOptions->getFilterExpressionFunctionsNonStatic() as $operatorName => $operatorOptions) {
            $event->addOperator($operatorName, $operatorOptions);
        }
    }

    public function onGenerateSegmentFiltersAddCustomFields(LeadListFiltersChoicesEvent $event): void
    {
        $this->leadFieldRepository->getListablePublishedFields()->map(function (LeadField $field) use ($event): void {
            $type               = $field->getType();
            $properties         = $field->getProperties();
            $properties['type'] = $type;

            if ('boolean' === $type) {
                $properties['list'] = [
                    $properties['no']  => 0,
                    $properties['yes'] => 1,
                ];
            } elseif (in_array($type, ['select', 'multiselect'], true)) {
                $properties['list'] = FormFieldHelper::parseListForChoices($properties['list'] ?? []);
            } else {
                try {
                    $properties['list'] = $this->fieldChoicesProvider->getChoicesForField($type, $field->getAlias());
                } catch (ChoicesNotFoundException) {
                    // That's fine. Not all fields should have choices.
                }
            }

            $event->addChoice(
                $field->getObject(),
                $field->getAlias(),
                [
                    'label'      => $field->getLabel(),
                    'properties' => $properties,
                    'object'     => $field->getObject(),
                    'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType($type),
                ]
            );
        });
    }

    public function onGenerateSegmentFiltersAddStaticFields(LeadListFiltersChoicesEvent $event): void
    {
        $event->addChoice(
            'lead',
            'leadlist',
            [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lists'),
                'properties' => [
                    'type' => 'leadlist',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('multiselect', 'leadlist', $event->getSearch()),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ]
        );

        // Only show for segments and not dynamic content addressed by https://github.com/mautic/mautic/pull/9260
        if (!$event->isForSegmentation()) {
            return;
        }

        $this->setIncludeExcludeOperatorsToTextFilters($event);
        $staticFields = [
            'date_added' => [
                'label'      => $this->translator->trans('mautic.core.date.added'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_identified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_identified'),
                'properties' => ['type' => 'date'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'last_active' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.last_active'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'date_modified' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.date_modified'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'owner_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.owner'),
                'properties' => [
                    'type'     => 'lookup_id',
                    'callback' => 'activateSegmentFilterTypeahead',
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('lookup_id'),
                'object'    => 'lead',
            ],
            'points' => [
                'label'      => $this->translator->trans('mautic.lead.lead.event.points'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.campaign'),
                'properties' => [
                    'type' => 'campaign',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'campaign'),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ],
            'tags' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.tags'),
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'tags',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('multiselect', 'tags'),
                ],
            ],
            'device_type' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_type'),
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'device_type',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'device_type'),
                ],
            ],
            'device_brand' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_brand'),
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'device_brand',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('multiselect', 'device_brand'),
                ],
            ],
            'device_os' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_os'),
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'device_os',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('multiselect', 'device_os'),
                ],
            ],
            'device_model' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.device_model'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::LIKE,
                    OperatorOptions::REGEXP,
                ]),
                'object' => 'lead',
            ],
            'dnc_bounced' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_bounced'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_unsubscribed'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_manual_email' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_manual_email'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_manual_email'),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'     => 'lead',
            ],
            'dnc_bounced_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_bounced_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_bounced_sms'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_unsubscribed_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_unsubscribed_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_unsubscribed_sms'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'dnc_manual_sms' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.dnc_manual_sms'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'dnc_manual_sms'),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'     => 'lead',
            ],
            'stage' => [
                'label'      => $this->translator->trans('mautic.lead.lead.field.stage'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'stage',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'stage'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::EMPTY,
                    OperatorOptions::NOT_EMPTY,
                ]),
            ],
            'globalcategory' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.categories'),
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'globalcategory',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'globalcategory'),
                ],
            ],
            'utm_campaign' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcampaign'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'utm_content' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmcontent'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'utm_medium' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmmedium'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'utm_source' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmsource'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'utm_term' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.utmterm'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
        ];

        foreach ($staticFields as $alias => $fieldOptions) {
            $event->addChoice('lead', $alias, $fieldOptions);
        }
    }

    public function onGenerateSegmentFiltersAddBehaviors(LeadListFiltersChoicesEvent $event): void
    {
        // Only show for segments and not dynamic content addressed by https://github.com/mautic/mautic/pull/9260
        if (!$event->isForSegmentation()) {
            return;
        }

        $this->setIncludeExcludeOperatorsToTextFilters($event);
        $choices = [
            'lead_asset_download' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_asset_download'),
                'properties' => [
                    'type' => 'assets',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'lead_asset_download', $event->getSearch()),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ],
            'lead_email_received' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_received'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'lead_email_received',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'lead_email_received'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::IN,
                    OperatorOptions::NOT_IN,
                ]),
            ],
            'lead_email_sent' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent'),
                'object'     => 'lead',
                'properties' => [
                    'type' => 'lead_email_received',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('select', 'lead_email_sent'),
                ],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::IN,
                    OperatorOptions::NOT_IN,
                ]),
            ],
            'lead_email_sent_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_sent_date'),
                'object'     => 'lead',
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
            ],
            'lead_email_read_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            'lead_email_read_count' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.lead_email_read_count'),
                'object'     => 'lead',
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
            ],
            'hit_url' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::LIKE,
                    OperatorOptions::NOT_LIKE,
                    OperatorOptions::REGEXP,
                    OperatorOptions::NOT_REGEXP,
                    OperatorOptions::STARTS_WITH,
                    OperatorOptions::ENDS_WITH,
                    OperatorOptions::CONTAINS,
                ]),
                'object' => 'lead',
            ],
            'hit_url_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            'hit_url_count' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.visited_url_count'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            // Clicked any link from any email
            'email_id' => [ // kept as email_id for BC
                'label'      => $this->translator->trans('mautic.lead.list.filter.email_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'email_id'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            // Clicked any link from any email relative to time
            'email_clicked_link_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.email_clicked_link_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            // Clicked any link from any sms
            'sms_clicked_link' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.sms_clicked_link'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'sms_clicked_link'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            // Clicked any link from any sms relative to time
            'sms_clicked_link_date' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.sms_clicked_link_date'),
                'properties' => ['type' => 'datetime'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            'sessions' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.session'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::GREATER_THAN,
                    OperatorOptions::LESS_THAN,
                    OperatorOptions::GREATER_THAN_OR_EQUAL,
                    OperatorOptions::LESS_THAN_OR_EQUAL,
                ]),
                'object' => 'lead',
            ],
            'referer' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.referer'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::LIKE,
                    OperatorOptions::NOT_LIKE,
                    OperatorOptions::REGEXP,
                    OperatorOptions::NOT_REGEXP,
                    OperatorOptions::STARTS_WITH,
                    OperatorOptions::ENDS_WITH,
                    OperatorOptions::CONTAINS,
                ]),
                'object' => 'lead',
            ],
            'url_title' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.url_title'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::LIKE,
                    OperatorOptions::NOT_LIKE,
                    OperatorOptions::REGEXP,
                    OperatorOptions::NOT_REGEXP,
                    OperatorOptions::STARTS_WITH,
                    OperatorOptions::ENDS_WITH,
                    OperatorOptions::CONTAINS,
                ]),
                'object' => 'lead',
            ],
            'source' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.source'),
                'properties' => ['type' => 'text'],
                'operators'  => $this->typeOperatorProvider->getOperatorsIncluding([
                    OperatorOptions::EQUAL_TO,
                    OperatorOptions::NOT_EQUAL_TO,
                    OperatorOptions::LIKE,
                    OperatorOptions::NOT_LIKE,
                    OperatorOptions::REGEXP,
                    OperatorOptions::NOT_REGEXP,
                    OperatorOptions::STARTS_WITH,
                    OperatorOptions::ENDS_WITH,
                    OperatorOptions::CONTAINS,
                ]),
                'object' => 'lead',
            ],
            'source_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.source.id'),
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ],
            'notification' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.notification'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'notification'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'page_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.page_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'page_id'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
            'redirect_id' => [
                'label'      => $this->translator->trans('mautic.lead.list.filter.redirect_id'),
                'properties' => [
                    'type' => 'boolean',
                    'list' => $this->fieldChoicesProvider->getChoicesForField('boolean', 'redirect_id'),
                ],
                'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('bool'),
                'object'    => 'lead',
            ],
        ];

        foreach ($choices as $alias => $fieldOptions) {
            $event->addChoice('behaviors', $alias, $fieldOptions);
        }
    }

    private function setIncludeExcludeOperatorsToTextFilters(LeadListFiltersChoicesEvent $event): void
    {
        $choices = $event->getChoices();

        foreach ($choices as $group => $groups) {
            foreach ($groups as $alias => $choice) {
                $type = $choice['properties']['type'] ?? null;
                if ('text' === $type) {
                    $choices[$group][$alias]['operators'] = $this->typeOperatorProvider->getOperatorsIncluding([
                        OperatorOptions::EQUAL_TO,
                        OperatorOptions::NOT_EQUAL_TO,
                        OperatorOptions::EMPTY,
                        OperatorOptions::NOT_EMPTY,
                        OperatorOptions::LIKE,
                        OperatorOptions::NOT_LIKE,
                        OperatorOptions::REGEXP,
                        OperatorOptions::NOT_REGEXP,
                        OperatorOptions::IN,
                        OperatorOptions::NOT_IN,
                        OperatorOptions::STARTS_WITH,
                        OperatorOptions::ENDS_WITH,
                        OperatorOptions::CONTAINS,
                    ]);
                }
            }
        }

        $event->setChoices($choices);
    }
}
