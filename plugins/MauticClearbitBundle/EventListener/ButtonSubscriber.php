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
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;

class ButtonSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    private $helper;

    /**
     * ButtonSubscriber constructor.
     *
     * @param IntegrationHelper $helper
     */
    public function __construct(IntegrationHelper $helper)
    {
        parent::__construct();

        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    /**
     * @param CustomButtonEvent $event
     */
    public function injectViewButtons(CustomButtonEvent $event)
    {
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $this->helper->getIntegrationObject('Clearbit');

        if (false === $myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        if (0 === strpos($event->getRoute(), 'mautic_contact_')) {
            $event->addButton(
                [
                    'attr' => [
                        'class'       => 'btn btn-default btn-sm btn-nospin',
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        'onclick'     => 'this.href=\''.
                            $this->router->generate(
                                'mautic_plugin_clearbit_action',
                                ['objectAction' => 'batchLookupPerson']
                            ).
                            '?\' + mQuery.param({\'clearbit_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                        'data-header' => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                    ],
                    'btnText'   => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                    'iconClass' => 'fa fa-search',
                ],
                ButtonHelper::LOCATION_BULK_ACTIONS
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
                    'btnText'   => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                    'iconClass' => 'fa fa-search',
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
            if (0 === strpos($event->getRoute(), 'mautic_company_')) {
                $event->addButton(
                    [
                        'attr' => [
                            'class'       => 'btn btn-default btn-sm btn-nospin',
                            'data-toggle' => 'ajaxmodal',
                            'data-target' => '#MauticSharedModal',
                            'onclick'     => 'this.href=\''.
                                $this->router->generate(
                                    'mautic_plugin_clearbit_action',
                                    ['objectAction' => 'batchLookupCompany']
                                ).
                                '?\' + mQuery.param({\'clearbit_batch_lookup\':{\'ids\':JSON.parse(Mautic.getCheckedListIds(false, true))}});return true;',
                            'data-header' => $this->translator->trans(
                                'mautic.plugin.clearbit.button.caption'
                            ),
                        ],
                        'btnText'   => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                        'iconClass' => 'fa fa-search',
                    ],
                    ButtonHelper::LOCATION_BULK_ACTIONS
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
                        'btnText'   => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
                        'iconClass' => 'fa fa-search',
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
