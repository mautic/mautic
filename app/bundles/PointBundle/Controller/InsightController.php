<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\Response;

class InsightController extends AbstractFormController
{
    public function indexAction(): Response
    {
        if (!$this->security->isGranted('point:points:view')) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters' => [
                'items' => [],
            ],
            'contentTemplate' => '@MauticPoint/Insight/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_insights_index',
                'mauticContent' => 'pointInsight',
            ],
        ]);
    }
}
