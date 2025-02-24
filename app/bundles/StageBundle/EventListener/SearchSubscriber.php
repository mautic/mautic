<?php

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StageModel $stageModel,
        private CorePermissions $security,
        private Environment $twig
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
        if ($this->security->isGranted('stage:stages:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items = $this->stageModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);
            $stageCount = count($items);
            if ($stageCount > 0) {
                $stagesResults = [];
                $canEdit       = $this->security->isGranted('stage:stages:edit');
                foreach ($items as $item) {
                    $stagesResults[] = $this->twig->render(
                        '@MauticStage/SubscribedEvents/Search/global.html.twig',
                        [
                            'item'    => $item,
                            'canEdit' => $canEdit,
                        ]
                    );
                }
                if ($stageCount > 5) {
                    $stagesResults[] = $this->twig->render(
                        '@MauticStage/SubscribedEvents/Search/global.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($stageCount - 5),
                        ]
                    );
                }
                $stagesResults['count'] = $stageCount;
                $event->addResults('mautic.stage.actions.header.index', $stagesResults);
            }
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted('stage:stages:view')) {
            $event->addCommands(
                'mautic.stage.actions.header.index',
                $this->stageModel->getCommandList()
            );
        }
    }
}
