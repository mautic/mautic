<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Event\BuilderEvent;

trait BuilderModelTrait
{
    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     * @param BuilderEvent $event
     *
     * @return array
     */
    public function getCommonBuilderComponents($requestedComponents, BuilderEvent $event)
    {
        $singleComponent = (!is_array($requestedComponents) && $requestedComponents != 'all');
        $components      = [];

        if (!is_array($requestedComponents)) {
            $requestedComponents = [$requestedComponents];
        }
        foreach ($requestedComponents as $requested) {
            switch ($requested) {
                case 'tokens':
                    $components[$requested] = $event->getTokens();
                    break;
                case 'abTestWinnerCriteria':
                    $components[$requested] = $event->getAbTestWinnerCriteria();
                    break;
                case 'slotTypes':
                    $components[$requested] = $event->getSlotTypes();
                    break;
                case 'sections':
                    $components[$requested] = $event->getSections();
                    break;
                default:
                    $components['tokens']               = $event->getTokens();
                    $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
                    $components['slotTypes']            = $event->getSlotTypes();
                    $components['sections']             = $event->getSections();
                    break;
            }
        }

        return ($singleComponent) ? $components[$requestedComponents[0]] : $components;
    }
}
