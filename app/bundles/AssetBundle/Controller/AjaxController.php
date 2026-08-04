<?php

namespace Mautic\AssetBundle\Controller;

use Gaufrette\Filesystem;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\RemoteAssetBrowseEvent;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private AssetModel $assetModel;
    private IntegrationHelper $integrationHelper;

    #[Required]
    public function autowireAssetAjaxController(
        AssetModel $assetModel,
    ): void {
        $this->assetModel = $assetModel;
    }

    public function categoryListAction(Request $request): JsonResponse
    {
        $filter     = InputHelper::clean($request->query->get('filter'));
        $results    = $this->assetModel->getLookupResults('category', $filter, 10);
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
    public function fetchRemoteFilesAction(Request $request): JsonResponse
    {
        $provider   = InputHelper::string($request->request->get('provider'));
        $path       = InputHelper::string($request->request->get('path', ''));
        $name       = AssetEvents::ASSET_ON_REMOTE_BROWSE;
        if (!$this->dispatcher->hasListeners($name)) {
            return $this->sendJsonResponse(['success' => 0]);
        }

        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integration */
        $integration = $this->integrationHelper->getIntegrationObject($provider);

        $event = new RemoteAssetBrowseEvent($integration);
        $this->dispatcher->dispatch($event, $name);

        if (!$adapter = $event->getAdapter()) {
            return $this->sendJsonResponse([
                'success' => 0,
                'message' => $event->getFailureMessage() ?? null,
            ]);
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

    #[Required]
    public function autowireAssetAjaxController(
        IntegrationHelper $integrationHelper,
    ): void {
        $this->integrationHelper = $integrationHelper;
    }
}
