<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Gaufrette\Filesystem;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\RemoteAssetBrowseEvent;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function categoryListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->getModel('asset')->getLookupResults('category', $filter, 10);
        $dataArray = [];
        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']})",
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function fetchRemoteFilesAction(Request $request)
    {
        $provider   = InputHelper::string($request->request->get('provider'));
        $path       = InputHelper::string($request->request->get('path', ''));
        $dispatcher = $this->dispatcher;
        $name       = AssetEvents::ASSET_ON_REMOTE_BROWSE;

        if (!$dispatcher->hasListeners($name)) {
            return $this->sendJsonResponse(['success' => 0]);
        }

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');

        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integration */
        $integration = $integrationHelper->getIntegrationObject($provider);

        $event = new RemoteAssetBrowseEvent($integration);

        $dispatcher->dispatch($name, $event);

        if (!$adapter = $event->getAdapter()) {
            return $this->sendJsonResponse(['success' => 0]);
        }

        $connector = new Filesystem($adapter);

        $output = $this->renderView(
            'MauticAssetBundle:Remote:list.html.php',
            [
                'connector'   => $connector,
                'integration' => $integration,
                'items'       => $connector->listKeys($path),
            ]
        );

        return $this->sendJsonResponse(['success' => 1, 'output' => $output]);
    }
}
