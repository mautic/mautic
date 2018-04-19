<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\ButtonHelper;

class ButtonSubscriber extends CommonSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectContactBulkButtons', 0],
        ];
    }

    /**
     * @param CustomButtonEvent $event
     */
    public function injectContactBulkButtons(CustomButtonEvent $event)
    {
        if (0 === strpos($event->getRoute(), 'mautic_contact_')) {
            $event->addButton(
                [
                    'attr' => [
                        'class'       => 'btn btn-default btn-sm btn-nospin',
                        'data-toggle' => 'ajaxmodal',
                        'data-target' => '#MauticSharedModal',
                        'href'        => $this->router->generate('mautic_category_batch_contact_view'),
                        'data-header' => $this->translator->trans('mautic.lead.batch.categories'),
                    ],
                    'btnText'   => $this->translator->trans('mautic.lead.batch.categories'),
                    'iconClass' => 'fa fa-cogs',
                ],
                ButtonHelper::LOCATION_BULK_ACTIONS
            );
        }
    }
}
