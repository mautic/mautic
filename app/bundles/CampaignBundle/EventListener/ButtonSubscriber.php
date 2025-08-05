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

final class ButtonSubscriber implements EventSubscriberInterface
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
        if (!str_contains($event->getRoute(), 'mautic_campaign_index')) {
            return;
        }

        if (!$this->security->isGranted('campaign:export:enable', 'MATCH_ONE')) {
            return;
        }

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
}
