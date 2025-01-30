<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GeneratedColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ListModel $segmentModel,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD       => ['onGeneratedColumnsBuild', 0],
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onGenerateSegmentFilters', 0],
        ];
    }

    public function onGeneratedColumnsBuild(GeneratedColumnsEvent $event): void
    {
        $emailDomain = new GeneratedColumn(
            'leads',
            'generated_email_domain',
            'VARCHAR(255)',
            'SUBSTRING(email, LOCATE("@", email) + 1)'
        );

        $event->addGeneratedColumn($emailDomain);
    }

    public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event): void
    {
        $event->addChoice('lead', 'generated_email_domain', [
            'label'      => $this->translator->trans('mautic.email.segment.choice.generated_email_domain'),
            'properties' => ['type' => 'text'],
            'operators'  => $this->segmentModel->getOperatorsForFieldType(
                [
                    'include' => [
                        '=',
                        '!=',
                        'empty',
                        '!empty',
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
        ]);
    }
}
