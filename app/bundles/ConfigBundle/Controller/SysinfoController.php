<?php

namespace Mautic\ConfigBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SysinfoController.
 */
class SysinfoController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->user->isAdmin() || $this->coreParametersHelper->get('sysinfo_disabled')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\ConfigBundle\Model\SysinfoModel $model */
        $model   = $this->getModel('config.sysinfo');
        $phpInfo = $model->getPhpInfo();
        $folders = $model->getFolders();
        $log     = $model->getLogTail(40);
        $dbInfo  = $model->getDbInfo();

        return $this->delegateView([
            'viewParameters' => [
                'phpInfo' => $phpInfo,
                'folders' => $folders,
                'log'     => $log,
                'dbInfo'  => $dbInfo,
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
