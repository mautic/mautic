<?php

namespace Mautic\ConfigBundle\Controller;

use function assert;
use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SysinfoController extends FormController
{
    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->user->isAdmin() || $this->coreParametersHelper->get('sysinfo_disabled')) {
            return $this->accessDenied();
        }

        $model = $this->get('mautic.config.model.sysinfo');
        assert($model instanceof SysinfoModel);

        return $this->delegateView([
            'viewParameters' => [
                'phpInfo'         => $model->getPhpInfo(),
                'requirements'    => $model->getRequirements(),
                'recommendations' => $model->getRecommendations(),
                'folders'         => $model->getFolders(),
                'log'             => $model->getLogTail(200),
                'dbInfo'          => $model->getDbInfo(),
            ],
            'contentTemplate' => 'MauticConfigBundle:Sysinfo:index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sysinfo_index',
                'mauticContent' => 'sysinfo',
                'route'         => $this->generateUrl('mautic_sysinfo_index'),
            ],
        ]);
    }
}
