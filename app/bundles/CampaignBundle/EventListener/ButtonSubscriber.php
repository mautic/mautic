<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private CorePermissions $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    public function injectViewButtons(CustomButtonEvent $event): void
    {
        if (!$this->security->isGranted('campaign:export:enable', 'MATCH_ONE')) {
            return;
        }

        if (str_contains($event->getRoute(), 'mautic_campaign_index')) {
            $this->addListPageButtons($event);
        }

        if (str_contains($event->getRoute(), 'mautic_campaign_action')) {
            $this->addListRowExportButton($event);
        }
    }

    private function addListPageButtons(CustomButtonEvent $event): void
    {
        $exportRoute = $this->router->generate('mautic_campaign_action', ['objectAction' => 'batchExport']);

        $event->addButton(
            [
                'attr'      => [
                    'data-toggle'           => 'confirmation',
                    'href'                  => $exportRoute.'?filetype=zip',
                    'data-precheck'         => 'batchActionPrecheck',
                    'data-message'          => $this->translator->trans(
                        'mautic.core.export.items',
                        ['%items%' => 'campaigns']
                    ),
                    'data-confirm-text'     => $this->translator->trans('mautic.core.export.zip'),
                    'data-confirm-callback' => 'executeBatchAction',
                    'data-cancel-text'      => $this->translator->trans('mautic.core.form.cancel'),
                    'data-cancel-callback'  => 'dismissConfirmation',
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.zip'),
                'iconClass' => 'ri-file-zip-line',
            ],
            ButtonHelper::LOCATION_TOOLBAR_BULK_ACTIONS
        );
        $event->addButton(
            [
                'attr'      => [
                    'href'        => $exportRoute.'?filetype=zip',
                    'data-toggle' => null,
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.zip'),
                'iconClass' => 'ri-file-zip-line',
            ],
            ButtonHelper::LOCATION_PAGE_ACTIONS
        );
    }

    private function addListRowExportButton(CustomButtonEvent $event): void
    {
        $item = $event->getItem();
        if (null === $item || !method_exists($item, 'getId')) {
            return;
        }

        $shareRoute = $this->router->generate('mautic_campaign_action', [
            'objectAction' => 'share',
            'objectId'     => $item->getId(),
        ]);

        $event->addButton(
            [
                'attr'      => [
                    'href'  => $shareRoute,
                    'class' => 'btn btn-ghost btn-sm btn-nospin',
                ],
                'btnText'   => $this->translator->trans('mautic.core.export'),
                'iconClass' => 'ri-export-line',
                'priority'  => 150,
            ],
            ButtonHelper::LOCATION_LIST_ACTIONS
        );
    }
}
