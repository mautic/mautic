<?php

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IntegrationHelper $helper,
        private TranslatorInterface $translator,
        private RouterInterface $router,
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
        // get api_key from plugin settings
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $this->helper->getIntegrationObject('FullContact');

        if (false === $myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        if (str_starts_with($event->getRoute(), 'mautic_contact_')) {
            $event->addButton(
                [
                    'attr' => [
                        'class'       => 'btn btn-ghost btn-sm btn-nospin',
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        'onclick'     => 'this.href=\''.
                            $this->router->generate(
                                'mautic_plugin_fullcontact_action',
                                ['objectAction' => 'batchLookupPerson']
                            ).
                            '?\' + mQuery.param({\'fullcontact_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                        'data-header' => $this->translator->trans('mautic.plugin.fullcontact.button.caption'),
                    ],
                    'btnText'   => $this->translator->trans('mautic.plugin.fullcontact.button.caption'),
                    'iconClass' => 'ri-search-line',
                ],
                ButtonHelper::LOCATION_BULK_ACTIONS
            );

            if ($event->getItem()) {
                $lookupContactButton = [
                    'attr' => [
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        'data-header' => $this->translator->trans(
                            'mautic.plugin.fullcontact.lookup.header',
                            ['%item%' => $event->getItem()->getEmail()]
                        ),
                        'href' => $this->router->generate(
                            'mautic_plugin_fullcontact_action',
                            ['objectId' => $event->getItem()->getId(), 'objectAction' => 'lookupPerson']
                        ),
                    ],
                    'btnText'   => $this->translator->trans('mautic.plugin.fullcontact.button.caption'),
                    'iconClass' => 'ri-search-line',
                ];

                $event
                    ->addButton(
                        $lookupContactButton,
                        ButtonHelper::LOCATION_PAGE_ACTIONS,
                        ['mautic_contact_action', ['objectAction' => 'view']]
                    )
                    ->addButton(
                        $lookupContactButton,
                        ButtonHelper::LOCATION_LIST_ACTIONS,
                        'mautic_contact_index'
                    );
            }
        } else {
            if (str_starts_with($event->getRoute(), 'mautic_company_')) {
                $event->addButton(
                    [
                        'attr' => [
                            'class'       => 'btn btn-ghost btn-sm btn-nospin',
                            'data-toggle' => 'ajaxmodal',
                            'data-target' => '#MauticSharedModal',
                            'onclick'     => 'this.href=\''.
                                $this->router->generate(
                                    'mautic_plugin_fullcontact_action',
                                    ['objectAction' => 'batchLookupCompany']
                                ).
                                '?\' + mQuery.param({\'fullcontact_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                            'data-header' => $this->translator->trans(
                                'mautic.plugin.fullcontact.button.caption'
                            ),
                        ],
                        'btnText'   => $this->translator->trans('mautic.plugin.fullcontact.button.caption'),
                        'iconClass' => 'ri-search-line',
                    ],
                    ButtonHelper::LOCATION_BULK_ACTIONS
                );

                if ($event->getItem()) {
                    $lookupCompanyButton = [
                        'attr' => [
                            'data-toggle' => 'ajaxmodal',
                            'data-target' => '#MauticSharedModal',
                            'data-header' => $this->translator->trans(
                                'mautic.plugin.fullcontact.lookup.header',
                                ['%item%' => $event->getItem()->getName()]
                            ),
                            'href' => $this->router->generate(
                                'mautic_plugin_fullcontact_action',
                                ['objectId' => $event->getItem()->getId(), 'objectAction' => 'lookupCompany']
                            ),
                        ],
                        'btnText'   => $this->translator->trans('mautic.plugin.fullcontact.button.caption'),
                        'iconClass' => 'ri-search-line',
                    ];

                    $event
                        ->addButton(
                            $lookupCompanyButton,
                            ButtonHelper::LOCATION_LIST_ACTIONS,
                            'mautic_company_index'
                        );
                }
            }
        }
    }
}
