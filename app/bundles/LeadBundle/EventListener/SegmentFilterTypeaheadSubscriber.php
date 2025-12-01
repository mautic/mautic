<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\ListTypeaheadEvent;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SegmentFilterTypeaheadSubscriber implements EventSubscriberInterface
{
    public function __construct(private LeadModel $leadModel, private FieldModel $fieldModel, private CompanyModel $companyModel)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ListTypeaheadEvent::class => [
                ['onSegmentFilterAliasEmpty', 1],
                ['onSegmentFilterAliasUser', 0],
                ['onSegmentFilterFieldEmpty', 0],
                ['onSegmentFilterCanProvideTypeahead', 0],
                ['onSegmentFilterEntityByAlias', 0],
            ],
        ];
    }

    public function onSegmentFilterAliasEmpty(ListTypeaheadEvent $event): void
    {
        if (!empty($event->getFieldAlias())) {
            return;
        }

        $dataArray['error']   = 'Alias cannot be empty';
        $dataArray['success'] = 0;

        $event->setDataArray($dataArray);
        $event->stopPropagation();
    }

    public function onSegmentFilterAliasUser(ListTypeaheadEvent $event): void
    {
        if ('owner_id' !== $event->getFieldAlias()) {
            return;
        }

        $results   = $this->leadModel->getLookupResults('user', $event->getFilter());

        $dataArray = [];
        foreach ($results as $r) {
            $name        = $r['firstName'].' '.$r['lastName'];
            $dataArray[] = [
                'value' => $name,
                'id'    => $r['id'],
            ];
        }

        $event->setDataArray($dataArray);
        $event->stopPropagation();
    }

    public function onSegmentFilterFieldEmpty(ListTypeaheadEvent $event): void
    {
        $field      = $this->fieldModel->getEntityByAlias($event->getFieldAlias());

        if (!empty($field)) {
            return;
        }

        $event->stopPropagation();
    }

    public function onSegmentFilterCanProvideTypeahead(ListTypeaheadEvent $event): void
    {
        $field      = $this->fieldModel->getEntityByAlias($event->getFieldAlias());

        // Select field types that make sense to provide typeahead for.
        $isLookup     = in_array($field->getType(), ['lookup']);
        $shouldLookup = in_array($field->getAlias(), ['city', 'company', 'title']);

        if ($isLookup && $shouldLookup) {
            return;
        }

        $event->stopPropagation();
    }

    public function onSegmentFilterEntityByAlias(ListTypeaheadEvent $event): void
    {
        $fieldAlias = $event->getFieldAlias();
        $filter     = $event->getFilter();

        $field      = $this->fieldModel->getEntityByAlias($fieldAlias);

        $dataArray = [];
        if ('lookup' === $field->getType() && !empty($field->getProperties()['list'])) {
            foreach ($field->getProperties()['list'] as $predefinedValue) {
                $dataArray[] = ['value' => $predefinedValue];
            }
        }

        if ('company' === $field->getObject()) {
            $results = $this->companyModel->getLookupResults('companyfield', [$fieldAlias, $filter]);
            foreach ($results as $r) {
                $dataArray[] = ['value' => $r['label']];
            }
        } elseif ('lead' === $field->getObject()) {
            $results = $this->fieldModel->getLookupResults($fieldAlias, $filter);
            foreach ($results as $r) {
                $dataArray[] = ['value' => $r[$fieldAlias]];
            }
        }

        $event->setDataArray($dataArray);
        $event->stopPropagation();
    }
}
