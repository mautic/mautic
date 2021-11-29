<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    private ComposerHelper $composer;
    private CacheHelper $cacheHelper;

    public function __construct(ComposerHelper $composer, CacheHelper $cacheHelper)
    {
        $this->composer    = $composer;
        $this->cacheHelper = $cacheHelper;
    }

    public function installPackageAction(Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('test'),
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if ($this->composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => 'TODO already installed',
            ], 400);
        }

        /**
         * We first try clearing the cache to be 100% sure we can clear without problems. If we install a plugin
         * and cache clearing fails, users very likely will get server errors. We'd rather be safe than
         * sorry and clear the cache before and after installing the plugin.
         */
        $exitCode = $this->cacheHelper->clearSymfonyCache();

        // TODO error handling
        $this->composer->install($packageName);

        // TODO error handling
        $exitCode = $this->cacheHelper->clearSymfonyCache();

        return new JsonResponse(['success' => true]);
    }

    public function removePackageAction(Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('test'),
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if (!$this->composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => 'TODO plugin not installed, cant remove',
            ], 400);
        }

        /**
         * We first try clearing the cache to be 100% sure we can clear without problems. If we delete a plugin
         * and cache clearing fails, users very likely will get server errors. We'd rather be safe than
         * sorry and clear the cache before and after removing the plugin.
         */
        $exitCode = $this->cacheHelper->clearSymfonyCache();

        $composerResult = $this->composer->remove($packageName);

        if (0 !== $composerResult->exitCode) {
            return $this->sendJsonResponse([
                'error' => 'Error while removing package using Composer:'.$composerResult->output,
            ], 500);
        }

        // Note: do not do anything except returning a response after clearing the cache
        // TODO error handling
        $exitCode = $this->cacheHelper->clearSymfonyCache();

        return new JsonResponse(['success' => true]);
    }
}
