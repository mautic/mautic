<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PluginApiController.
 */
class PluginApiController extends CommonApiController
{
     /**
     * @param $name
     *
     * @return JsonResponse|Response
     */
    public function viewSettingsAction($name)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $integrationHelper = $this->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject($name);
        if ($integrationObject && $integrationObject->getIntegrationSettings()->getIsPublished()) {
            return new JsonResponse($integrationObject->getIntegrationSettings()->getFeatureSettings());
        }

        return $this->notFound();
    }
}
