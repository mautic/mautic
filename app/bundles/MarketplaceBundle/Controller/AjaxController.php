<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
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

    private ResourceInstallerInterface $resourceInstaller;

    private PackageModel $packageModel;

    #[Required]
    public function autowireMarketplaceAjaxController(
        ComposerHelper $composer,
        CacheHelper $cacheHelper,
        LoggerInterface $logger,
        Config $config,
        ResourceInstallerInterface $resourceInstaller,
        PackageModel $packageModel,
    ): void {
        $this->composer          = $composer;
        $this->cacheHelper       = $cacheHelper;
        $this->logger            = $logger;
        $this->config            = $config;
        $this->resourceInstaller = $resourceInstaller;
        $this->packageModel      = $packageModel;
    }

    public function installPackageAction(Request $request): JsonResponse
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.marketplace_disabled'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_INSTALL_PACKAGES)) {
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

        try {
            $packageDetail = $this->packageModel->getPackageDetail($packageName);
            $type          = $packageDetail->packageBase->type ?? '';
        } catch (\Exception $e) {
            return $this->installError($e);
        }

        if ('mautic-resource' === $type) {
            if ($this->resourceInstaller->isInstalled($packageName)) {
                return $this->sendJsonResponse([
                    'error' => $this->translator->trans('marketplace.package.install.already.installed'),
                ], 400);
            }

            return $this->installResource($packageName);
        }

        if (!$this->config->isComposerEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.no_permissions'),
            ], Response::HTTP_FORBIDDEN);
        }

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

        return new JsonResponse([]);
    }

    public function removePackageAction(Request $request): JsonResponse
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.marketplace_disabled'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_REMOVE_PACKAGES)) {
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

        try {
            $packageDetail = $this->packageModel->getPackageDetail($packageName);
            $type          = $packageDetail->packageBase->type ?? '';
        } catch (\Exception $e) {
            return $this->removeError($e);
        }

        if ('mautic-resource' === $type) {
            if (!$this->resourceInstaller->isInstalled($packageName)) {
                return $this->sendJsonResponse([
                    'error' => $this->translator->trans('marketplace.package.remove.not.installed'),
                ], 400);
            }

            try {
                $this->resourceInstaller->uninstall($packageName);
            } catch (\Exception $e) {
                return $this->removeError($e);
            }

            return new JsonResponse([]);
        }

        if (!$this->config->isComposerEnabled()) {
            return $this->sendJsonResponse([
                'error' => $this->translator->trans('marketplace.package.request.no_permissions'),
            ], Response::HTTP_FORBIDDEN);
        }

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

        return new JsonResponse([]);
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

    private function installResource(string $packageName): JsonResponse
    {
        // CommonController resolves the current user for us, so the helper is no longer injected.
        $userId = (int) $this->user?->getId();

        try {
            $result = $this->resourceInstaller->install($packageName, $userId);
        } catch (\Exception $e) {
            return $this->installError($e);
        }

        if (!$result['success']) {
            $errorMessage = implode('; ', $result['errors']);
            $this->logger->error('Resource installation failed: '.$errorMessage);

            return $this->sendJsonResponse([
                'error' => $errorMessage,
            ], 500);
        }

        return new JsonResponse([]);
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
