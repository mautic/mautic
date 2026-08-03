<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private FocusModel $focusModel;
    private CacheProviderTagAwareInterface $cacheProvider;

    #[Required]
    public function autowireMauticFocusAjaxController(
        FocusModel $focusModel,
        CacheProviderTagAwareInterface $cacheProvider,
    ): void {
        $this->focusModel = $focusModel;
        $this->cacheProvider = $cacheProvider;
    }

    public function generatePreviewAction(Request $request): JsonResponse
    {
        $responseContent  = ['html' => '', 'style' => ''];
        $focus            = $request->request->all();

        if (isset($focus['focus'])) {
            $focusArray = InputHelper::_($focus['focus']);

            if (!empty($focusArray['style']) && !empty($focusArray['type'])) {
                $focusArray['id']         = 'preview';
                $responseContent['html']  = $this->focusModel->getContent($focusArray, true);
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
            $cacheItemValue   = $cacheItem->get();
            $viewsCount       = $cacheItemValue['views'];
            $uniqueViewsCount = $cacheItemValue['uniqueViews'];
        } else {
            $focus = $this->focusModel->getEntity($focusId);
            if (null === $focus) {
                return $this->sendJsonResponse([
                    'success' => 0,
                    'message' => $this->translator->trans('mautic.api.call.notfound'),
                ], 404);
            }
            $viewsCount       = $this->focusModel->getViewsCount($focus);
            $uniqueViewsCount = $this->focusModel->getUniqueViewsCount($focus);
            $cacheItem->set([
                'views'       => $viewsCount,
                'uniqueViews' => $uniqueViewsCount,
            ]);
            $cacheItem->tag("focus.{$focusId}");
            $cacheItem->expiresAfter($cacheTimeout * 60);
            $this->cacheProvider->save($cacheItem);
        }

        return $this->sendJsonResponse([
            'success'     => 1,
            'views'       => $viewsCount,
            'uniqueViews' => $uniqueViewsCount,
        ]);
    }

    public function getClickThroughCountAction(Request $request): JsonResponse
    {
        $focusId = (int) InputHelper::clean($request->query->get('focusId'));

        if (0 === $focusId) {
            return $this->sendJsonResponse([
                'success' => 0,
                'message' => $this->translator->trans('mautic.core.error.badrequest'),
            ], 400);
        }

        $cacheTimeout = (int) $this->coreParametersHelper->get('cached_data_timeout');
        $cacheItem    = $this->cacheProvider->getItem('focus.clickThroughCount.'.$focusId);

        if ($cacheItem->isHit()) {
            $clickThroughCount = $cacheItem->get();
        } else {
            $focus = $this->focusModel->getEntity($focusId);
            if (null === $focus) {
                return $this->sendJsonResponse([
                    'success' => 0,
                    'message' => $this->translator->trans('mautic.api.call.notfound'),
                ], 404);
            }
            $clickThroughCount = $this->focusModel->getClickThroughCount($focus);
            $cacheItem->set($clickThroughCount);
            $cacheItem->tag("focus.{$focusId}");
            $cacheItem->expiresAfter($cacheTimeout * 60);
            $this->cacheProvider->save($cacheItem);
        }

        return $this->sendJsonResponse([
            'success'        => 1,
            'clickThrough'   => $clickThroughCount,
        ]);
    }
}
