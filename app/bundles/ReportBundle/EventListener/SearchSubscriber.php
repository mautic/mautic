<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReportModel $reportModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->reportModel,
            '@MauticReport/SubscribedEvents/Search/global.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.report.reports', $results);
        }
    }

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
