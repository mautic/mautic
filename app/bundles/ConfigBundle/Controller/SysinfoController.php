<?php

namespace Mautic\ConfigBundle\Controller;

use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SysinfoController extends FormController
{
    private SysinfoModel $sysinfoModel;

    public function __construct(SysinfoModel $sysinfoModel)
    {
        $this->sysinfoModel = $sysinfoModel;
    }

    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->user->isAdmin() || $this->coreParametersHelper->get('sysinfo_disabled')) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters' => [
                'phpInfo'         => $this->sysinfoModel->getPhpInfo(),
                'requirements'    => $this->sysinfoModel->getRequirements(),
                'recommendations' => $this->sysinfoModel->getRecommendations(),
                'folders'         => $this->sysinfoModel->getFolders(),
                'log'             => $this->sysinfoModel->getLogTail(200),
                'dbInfo'          => $this->sysinfoModel->getDbInfo(),
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
