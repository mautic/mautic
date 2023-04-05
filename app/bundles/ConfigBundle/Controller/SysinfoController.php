<?php

namespace Mautic\ConfigBundle\Controller;

use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SysinfoController extends FormController
{
    private SysinfoModel $sysinfoModel;

    public function __construct(CorePermissions $security, UserHelper $userHelper, FormFactoryInterface $formFactory, FormFieldHelper $fieldHelper, SysinfoModel $sysinfoModel)
    {
        $this->sysinfoModel = $sysinfoModel;
        parent::__construct($security, $userHelper, $formFactory, $fieldHelper);
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
            'contentTemplate' => '@MauticConfig/Sysinfo/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sysinfo_index',
                'mauticContent' => 'sysinfo',
                'route'         => $this->generateUrl('mautic_sysinfo_index'),
            ],
        ]);
    }
}
