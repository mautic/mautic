# Segment filter extension pattern

Adding a segment filter means handling three events from `LeadEvents`:

1. `LIST_FILTERS_CHOICES_ON_GENERATE`: register the filter in the segment builder UI (label, type, operators, group).
2. `COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE`: provide dropdown options (list of forms, emails, assets).
3. `SEGMENT_DICTIONARY_ON_GENERATE`: register query metadata (table, join columns, query builder type).

## Query builder types

All under `app/bundles/LeadBundle/Segment/Query/Filter/`:

- `ForeignValueFilterQueryBuilder`: foreign table joins (`form_submissions`, `email_stats`, `asset_downloads`).
- `ForeignFuncFilterQueryBuilder`: aggregations (SUM, COUNT) across foreign tables.
- `BaseFilterQueryBuilder`: direct column filtering on `leads`.
- `ChannelClickQueryBuilder`: email and SMS click tracking via `page_hits`.
- `DoNotContactFilterQueryBuilder`: DNC flags.
- `SessionsFilterQueryBuilder`: session counts.
- `ComplexRelationValueFilterQueryBuilder`, `IntegrationCampaignFilterQueryBuilder`: relation and integration campaign filters.

## Key files

- `LeadBundle/Services/ContactSegmentFilterDictionary.php`: central dictionary of filter query metadata.
- `LeadBundle/EventListener/FilterOperatorSubscriber.php`: registers built-in filter UI choices.
- `LeadBundle/EventListener/TypeOperatorSubscriber.php`: provides field choices and controls form widget rendering.

## Gotcha: the `$isSelect` list

A new select-type filter must be added to the `$isSelect` list in `TypeOperatorSubscriber::onSegmentFilterFormHandleSelect()`. Without it the filter renders as a plain text input instead of a dropdown:

```php
$isSelect = $event->fieldTypeIsOneOf('select', 'multiselect', 'boolean', ..., 'your_new_type');
```

## Foreign table example

```php
// EventListener/SegmentFilterSubscriber.php, auto-discovered
class SegmentFilterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE            => 'onFilterChoices',
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => 'onTypeListCollect',
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE             => 'onDictionaryGenerate',
        ];
    }

    public function onDictionaryGenerate(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('your_filter_key', [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'your_table',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'the_field_to_filter',
        ]);
    }
}
```

The segment is rebuilt by `mautic:segments:update`; in a functional test, run it through `ApplicationTester` (see `SKILL.md`) and assert on the resulting membership rather than on the generated SQL.
