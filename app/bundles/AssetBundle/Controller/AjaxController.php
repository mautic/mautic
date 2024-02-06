<?php

namespace Mautic\AssetBundle\Controller;

use Gaufrette\Filesystem;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\RemoteAssetBrowseEvent;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    public function categoryListAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $assetModel = $this->getModel('asset');
        \assert($assetModel instanceof AssetModel);
        $filter     = InputHelper::clean($request->query->get('filter'));
        $results    = $assetModel->getLookupResults('category', $filter, 10);
        $dataArray  = [];
        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']})",
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @throws \Exception
     */
    public function fetchRemoteFilesAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
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

        $dispatcher->dispatch($event, $name);

        if (!$adapter = $event->getAdapter()) {
            return $this->sendJsonResponse(['success' => 0]);
        }

        $connector = new Filesystem($adapter);

        $output = $this->renderView(
            '@MauticAsset/Remote/list.html.twig',
            [
                'connector'   => $connector,
                'integration' => $integration,
                'items'       => $connector->listKeys($path),
            ]
        );

        return $this->sendJsonResponse(['success' => 1, 'output' => $output]);
    }
}
