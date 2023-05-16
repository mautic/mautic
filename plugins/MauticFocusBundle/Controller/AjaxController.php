<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    private CacheProvider $cacheProvider;

    public function __construct(ManagerRegistry $doctrine, CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;

        parent::__construct($doctrine);
    }

    /**
     * This method produces HTTP request checking headers which are blocking availability for iframe inheritance for other pages.
     */
    protected function checkIframeAvailabilityAction(Request $request, IframeAvailabilityChecker $availabilityChecker): JsonResponse
    {
        $url = $request->query->get('website');

        return $availabilityChecker->check($url, $request->getScheme());
    }

    protected function generatePreviewAction(Request $request): JsonResponse
    {
        $responseContent  = ['html' => '', 'style' => ''];
        $focus            = $request->request->all();

        if (isset($focus['focus'])) {
            $focusArray = InputHelper::_($focus['focus']);

            if (!empty($focusArray['style']) && !empty($focusArray['type'])) {
                /** @var FocusModel $model */
                $model                    = $this->getModel('focus');
                $focusArray['id']         = 'preview';
                $responseContent['html']  = $model->getContent($focusArray, true);
                $responseContent['style'] = $focusArray['style']; // Required by JS in response
            }
        }

        return $this->sendJsonResponse($responseContent);
    }

    public function getViewsCountAction(Request $request): JsonResponse
    {
        $focusId = (int) InputHelper::clean($request->query->get('focusId'));

        if (0 === $focusId) {
            return $this->sendJsonResponse([
                'success' => 0,
                'message' => $this->translator->trans('mautic.core.error.badrequest'),
            ], 400);
        }

        $cacheTimeout = (int) $this->coreParametersHelper->get('cached_data_timeout');
        $cacheItem    = $this->cacheProvider->getItem('focus.viewsCount.'.$focusId);

        if ($cacheItem->isHit()) {
            $viewsCount = $cacheItem->get();
        } else {
            /** @var FocusModel $model */
            $model   = $this->getModel('focus');

            $focus = $model->getEntity($focusId);
            if (null === $focus) {
                return $this->sendJsonResponse([
                    'success' => 0,
                    'message' => $this->translator->trans('mautic.api.call.notfound'),
                ], 404);
            }
            $viewsCount = $model->getViewsCount($focus);
            $cacheItem->set($viewsCount);
            $cacheItem->expiresAfter($cacheTimeout * 60);
            $this->cacheProvider->save($cacheItem);
        }

        return $this->sendJsonResponse([
            'success' => 1,
            'views'   => $viewsCount,
        ]);
    }
}
