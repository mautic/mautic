<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\SegmentFilterIconTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GeneratedColumnSubscriber
{
    use SegmentFilterIconTrait;

    public function __construct(
        private ListModel $segmentModel,
        private TranslatorInterface $translator,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: GeneratedColumnsEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: LeadListFiltersChoicesEvent::class)]
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
            'object'    => 'lead',
            'iconClass' => $this->getSegmentFilterIcon('generated_email_domain'),
        ]);
    }
}
