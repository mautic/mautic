<?php

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Event\BuilderEvent;

trait BuilderModelTrait
{
    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     *
     * @return array
     */
    public function getCommonBuilderComponents($requestedComponents, BuilderEvent $event)
    {
        $singleComponent = (!is_array($requestedComponents) && 'all' != $requestedComponents);
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
                default:
                    $components['tokens']               = $event->getTokens();
                    $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
                    break;
            }
        }

        return ($singleComponent) ? $components[$requestedComponents[0]] : $components;
    }
}
