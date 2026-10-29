<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\ReportBundle\Model\ReportModel;

final readonly class SearchSubscriber
{
    public function __construct(
        private ReportModel $reportModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->reportModel,
            '@MauticReport/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.report.reports', $results);
        }
    }

    #[AsEventListener(event: MauticEvents\CommandListEvent::class)]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted(['report:reports:viewown', 'report:reports:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.report.reports',
                $this->reportModel->getCommandList()
            );
        }
    }
}
