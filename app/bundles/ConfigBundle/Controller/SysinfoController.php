<?php

namespace Mautic\ConfigBundle\Controller;

use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class SysinfoController extends AbstractStandardFormController
{
    private SysinfoModel $sysinfoModel;

    #[Required]
    public function autowireSysinfoController(
        SysinfoModel $sysinfoModel,
    ): void {
        $this->sysinfoModel = $sysinfoModel;
    }

    protected function getModelName(): string
    {
        return '';
    }

    public function indexAction(): Response
    {
        if (!$this->user->isAdmin() || $this->coreParametersHelper->get('sysinfo_disabled')) {
            $this->throwAccessDenied();
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
