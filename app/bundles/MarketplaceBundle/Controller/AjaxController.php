<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private ComposerHelper $composer;

    private CacheHelper $cacheHelper;

    private LoggerInterface $logger;

    private Config $config;

    #[Required]
    public function autowireMarketplaceAjaxController(
        ComposerHelper $composer,
        CacheHelper $cacheHelper,
        LoggerInterface $logger,
        Config $config,
    ): void {
        $this->composer    = $composer;
        $this->cacheHelper = $cacheHelper;
        $this->logger      = $logger;
        $this->config      = $config;
    }

    public function installPackageAction(Request $request): JsonResponse
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.marketplace_disabled'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            || !$this->config->isComposerEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.no_permissions'),
            ], Response::HTTP_FORBIDDEN);
        }

        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.details.missing'),
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if ($this->composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.install.already.installed'),
            ], 400);
        }

        try {
            $installResult = $this->composer->install($packageName);

            if (Command::SUCCESS !== $installResult->exitCode) {
                return $this->installError(new \Exception($installResult->output));
            }
        } catch (\Exception $e) {
            return $this->installError($e);
        }

        // Note: do not do anything except returning a response after clearing the cache to prevent errors
        $clearCacheResult = $this->clearCacheOrReturnError();

        if (null !== $clearCacheResult) {
            return $clearCacheResult;
        }

        return new JsonResponse(['success' => true]);
    }

    public function removePackageAction(Request $request): JsonResponse
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.marketplace_disabled'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            || !$this->config->isComposerEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.no_permissions'),
            ], Response::HTTP_FORBIDDEN);
        }

        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.details.missing'),
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if (!$this->composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.remove.not.installed'),
            ], 400);
        }

        try {
            $removeResult = $this->composer->remove($packageName);

            if (0 !== $removeResult->exitCode) {
                return $this->removeError(new \Exception($removeResult->output));
            }
        } catch (\Exception $e) {
            return $this->removeError($e);
        }

        // Note: do not do anything except returning a response after clearing the cache to prevent errors
        $clearCacheResult = $this->clearCacheOrReturnError();

        if (null !== $clearCacheResult) {
            return $clearCacheResult;
        }

        return new JsonResponse(['success' => true]);
    }

    private function clearCacheOrReturnError(): ?JsonResponse
    {
        try {
            $exitCode = $this->cacheHelper->clearSymfonyCache();

            if (0 !== $exitCode) {
                $this->logger->error('Could not clear Mautic\'s cache. Please try again.');

                return $this->sendJsonResponse([
                    'error' => $this->translator->trans('marketplace.package.cache.clear.failed'),
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Could not clear Mautic\'s cache. Details: '.$e->getMessage());

            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.cache.clear.failed'),
            ], 500);
        }

        return null;
    }

    private function installError(\Exception $e): JsonResponse
    {
        $this->logger->error('Installation of plugin through Composer has failed: '.$e->getMessage());

        return $this->sendJsonResponse([
            'error' => $this->translator->trans('marketplace.package.install.failed'),
        ], 500);
    }

    private function removeError(\Exception $e): JsonResponse
    {
        $this->logger->error('Error while removing package through Composer: '.$e->getMessage());

        return $this->sendJsonResponse([
            'error' => $this->translator->trans('marketplace.package.remove.failed'),
        ], 500);
    }
}
