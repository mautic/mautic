<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentFilterSubscriber implements EventSubscriberInterface
{
    private LeagueRepository $leagueRepository;

    private TypeOperatorProviderInterface $typeOperatorProvider;

    private TranslatorInterface $translator;

    public function __construct(
        LeagueRepository $leagueRepository,
        TypeOperatorProviderInterface $typeOperatorProvider,
        TranslatorInterface $translator)
    {
        $this->leagueRepository     = $leagueRepository;
        $this->typeOperatorProvider = $typeOperatorProvider;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE   => [
                ['onGenerateSegmentFiltersAddPointLeagues', -10],
            ],
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE   => [
                ['onSegmentDictionaryGenerate', 0],
            ],
        ];
    }

    public function onGenerateSegmentFiltersAddPointLeagues(LeadListFiltersChoicesEvent $event): void
    {
        // Only show for segments and not dynamic content addressed by https://github.com/mautic/mautic/pull/9260
        if (!$event->isForSegmentation()) {
            return;
        }

        $leagues = $this->leagueRepository->getEntities();
        $choices = [];

        /** @var League $league */
        foreach ($leagues as $league) {
            $choices['league_points_'.$league->getId()] = [
                'label'      => $this->translator->trans('mautic.lead.lead.event.leaguepoints', ['%league%' => $league->getName()]),
                'properties' => ['type' => 'number'],
                'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ];
        }

        foreach ($choices as $alias => $fieldOptions) {
            $event->addChoice('leagues', $alias, $fieldOptions);
        }
    }

    public function onSegmentDictionaryGenerate(SegmentDictionaryGenerationEvent $event): void
    {
        $leagues = $this->leagueRepository->getEntities();

        /** @var League $league */
        foreach ($leagues as $league) {
            $event->addTranslation('league_points_'.$league->getId(), [
                'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
                'foreign_table'       => 'league_contact_score',
                'foreign_table_field' => 'contact_id',
                'table'               => 'leads',
                'table_field'         => 'id',
                'field'               => 'score',
                'where'               => 'league_contact_score.league_id = '.$league->getId(),
                'null_value'          => 0,
            ]);
        }
    }
}
