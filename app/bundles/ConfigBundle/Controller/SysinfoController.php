<?php

namespace Mautic\ConfigBundle\Controller;

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

        /** @var \Mautic\ConfigBundle\Model\SysinfoModel $model */
        $model = $this->getModel('config.sysinfo');

        return $this->delegateView([
            'viewParameters' => [
                'phpInfo'         => $model->getPhpInfo(),
                'requirements'    => $model->getRequirements(),
                'recommendations' => $model->getRecommendations(),
                'folders'         => $model->getFolders(),
                'log'             => $model->getLogTail(200),
                'dbInfo'          => $model->getDbInfo(),
            ],
            'contentTemplate' => 'MauticConfigBundle:Sysinfo:index.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sysinfo_index',
                'mauticContent' => 'sysinfo',
                'route'         => $this->generateUrl('mautic_sysinfo_index'),
            ],
        ]);
    }
}
