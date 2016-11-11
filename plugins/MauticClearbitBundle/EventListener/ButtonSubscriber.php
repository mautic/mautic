<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ButtonSubscriber extends CommonSubscriber
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * LeadSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        parent::__construct();
        $this->container = $container;
    }
    
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0]
        ];
    }

    /**
     * @param CustomButtonEvent $event
     */
    public function injectViewButtons(CustomButtonEvent $event)
    {
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        if (0 === strpos($event->getRoute(), 'mautic_contact_')) {

            $event->appendButton(
                [
                    'attr' => [
                        'class' => 'btn btn-default btn-sm btn-nospin',
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        // 'href'        => $this->router->generate('mautic_plugin_clearbit_action', ['objectAction' => 'batchLookupPerson']),
                        'onclick' => 'this.href=\''.
                            $this->router->generate(
                                'mautic_plugin_clearbit_action',
                                ['objectAction' => 'batchLookupPerson']
                            ).
                            '?\' + mQuery.param({\'clearbit_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                        'data-header' => $this->translator->trans('mautic.plugin.clearbit.toolbar_button.tooltip'),
                    ],
                    'tooltip' => $this->translator->trans('mautic.plugin.clearbit.toolbar_button.tooltip'),
                    'iconClass' => 'fa fa-search',
                ],
                ButtonHelper::LOCATION_TOOLBAR_ACTIONS
            );

            if ($event->getItem()) {
                $lookupContactButton = [
                    'attr' => [
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        'data-header' => $this->translator->trans(
                            'mautic.plugin.clearbit.lookup.header',
                            ['%item%' => $event->getItem()->getEmail()]
                        ),
                        'href' => $this->router->generate(
                            'mautic_plugin_clearbit_action',
                            ['objectId' => $event->getItem()->getId(), 'objectAction' => 'lookupPerson']
                        ),
                    ],
                    'btnText' => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                    'iconClass' => 'fa fa-search',
                ];

                $event
                    ->appendButton(
                        $lookupContactButton,
                        ButtonHelper::LOCATION_PAGE_ACTIONS,
                        ['mautic_contact_action', ['objectAction' => 'view']]
                    )
                    ->appendButton(
                        $lookupContactButton,
                        ButtonHelper::LOCATION_LIST_ACTIONS,
                        'mautic_contact_index'
                    );
            }
        } else {
            if (0 === strpos($event->getRoute(), 'mautic_company_')) {
                $event->appendButton(
                    [
                        'attr' => [
                            'class' => 'btn btn-default btn-sm btn-nospin',
                            'data-toggle' => 'ajaxmodal',
                            'data-target' => '#MauticSharedModal',
                            'onclick' => 'this.href=\''.
                                $this->router->generate(
                                    'mautic_plugin_clearbit_action',
                                    ['objectAction' => 'batchLookupCompany']
                                ).
                                '?\' + mQuery.param({\'clearbit_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                            'data-header' => $this->translator->trans(
                                'mautic.plugin.clearbit.toolbar_compbutton.tooltip'
                            ),
                        ],
                        'tooltip' => $this->translator->trans('mautic.plugin.clearbit.toolbar_compbutton.tooltip'),
                        'iconClass' => 'fa fa-search',
                    ],
                    ButtonHelper::LOCATION_TOOLBAR_ACTIONS
                );

                if ($event->getItem()) {
                    $lookupCompanyButton = [
                        'attr' => [
                            'data-toggle' => 'ajaxmodal',
                            'data-target' => '#MauticSharedModal',
                            'data-header' => $this->translator->trans(
                                'mautic.plugin.clearbit.lookup.header',
                                ['%item%' => $event->getItem()->getName()]
                            ),
                            'href' => $this->router->generate(
                                'mautic_plugin_clearbit_action',
                                ['objectId' => $event->getItem()->getId(), 'objectAction' => 'lookupCompany']
                            ),
                        ],
                        'btnText' => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                        'iconClass' => 'fa fa-search',
                    ];

                    $event
                        ->appendButton(
                            $lookupCompanyButton,
                            ButtonHelper::LOCATION_LIST_ACTIONS,
                            'mautic_company_index'
                        );
                }
            }
        }
    }
}