<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PluginApiController.
 */
class PluginApiController extends CommonApiController
{
    /**
     * @return Response
     */
    public function reloadAction()
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $view = $this->view(['response' => $this->get('mautic.plugin.facade.reload')->reloadPlugins()], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @param $integrationName
     *
     * @return Response
     */
    public function viewSettingsAction($integrationName)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $integrationHelper = $this->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject($integrationName);
        if ($integrationObject && $integrationObject->getIntegrationSettings()->getIsPublished()) {
            $view = $this->view($integrationObject->getIntegrationSettings()->getFeatureSettings(), Response::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * @param $package
     *
     * @return Response
     */
    public function installAction()
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        if (!is_callable('shell_exec') || false !== stripos(ini_get('disable_functions'), 'shell_exec')) {
            return $this->returnError($this->translator->trans('mautic.plugin.extension.shell_exec'), Response::HTTP_BAD_REQUEST);
        }

        $package = $this->get('request_stack')->getCurrentRequest()->get('package');
        @set_time_limit(9999);
        $response = shell_exec('composer require '.$package);
        $view     = $this->view(['response' => $response], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
