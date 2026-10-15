<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Entity\FormRepository;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FormRepository $formRepository,
        private TypeOperatorProviderInterface $typeOperatorProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => [
                ['onGenerateSegmentFiltersAddFormSubmission', 0],
            ],
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => [
                ['onTypeListCollect', 0],
            ],
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => [
                ['onSegmentDictionaryGenerate', 0],
            ],
        ];
    }

    public function onGenerateSegmentFiltersAddFormSubmission(LeadListFiltersChoicesEvent $event): void
    {
        if (!$event->isForSegmentation()) {
            return;
        }

        $event->addChoice('behaviors', 'lead_form_submission', [
            'label'      => $this->translator->trans('mautic.form.segment.filter.form_submission'),
            'properties' => [
                'type' => 'lead_form_submission',
                'list' => $this->getFormChoices(),
            ],
            'operators' => $this->typeOperatorProvider->getOperatorsForFieldType('multiselect'),
            'object'    => 'lead',
            'iconClass' => 'ri-survey-line',
        ]);
    }

    public function onTypeListCollect(ListFieldChoicesEvent $event): void
    {
        $event->setChoicesForFieldAlias('lead_form_submission', $this->getFormChoices());
    }

    public function onSegmentDictionaryGenerate(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('lead_form_submission', [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'form_submissions',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'form_id',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function getFormChoices(): array
    {
        $choices = [];
        $forms   = $this->formRepository->getFormList('', 0, 0, true);

        foreach ($forms as $form) {
            $choices[$form['name']] = (int) $form['id'];
        }

        return $choices;
    }
}
