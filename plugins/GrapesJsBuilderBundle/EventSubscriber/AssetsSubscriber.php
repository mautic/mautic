<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\InstallBundle\Install\InstallService;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AssetsSubscriber implements EventSubscriberInterface
{
    private const ASSET_DIR = 'plugins/GrapesJsBuilderBundle/Assets/library/js/dist';

    public function __construct(
        private Config $config,
        private InstallService $installer,
        private RequestStack $requestStack,
        private string $projectDir,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent): void
    {
        if (!$this->installer->checkIfInstalled() || !$this->isMauticAdministrationPage()) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $assetDir = $this->projectDir.'/'.self::ASSET_DIR;
        if (!is_file($assetDir.'/manifest.json')) {
            $this->logger->warning('GrapesJS builder assets are missing (no manifest.json). Run "composer gjs-build" to generate them.');

            return;
        }

        if ($js = $this->resolveAsset('builder.js')) {
            $assetsEvent->addScript(self::ASSET_DIR.'/'.$js);
        }
        if ($css = $this->resolveAsset('builder.css')) {
            $assetsEvent->addStylesheet(self::ASSET_DIR.'/'.$css);
        }
    }

    /**
     * Parcel content-hashes output filenames (e.g. builder.abc123.css), so resolve the
     * logical name to the actual file via the manifest.json emitted by the build.
     *
     * Returns null when the asset cannot be resolved to a file that exists on disk, so the
     * caller can skip injection rather than emit a broken path (which would redirect-loop).
     */
    private function resolveAsset(string $logicalName): ?string
    {
        $assetDir     = $this->projectDir.'/'.self::ASSET_DIR;
        $manifestPath = $assetDir.'/manifest.json';

        if (!is_file($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (!is_array($manifest) || !isset($manifest[$logicalName])) {
            return null;
        }

        $fileName = $manifest[$logicalName];

        // Guard against a malformed manifest entry pointing outside the asset dir.
        if (!is_string($fileName) || basename($fileName) !== $fileName || !is_file($assetDir.'/'.$fileName)) {
            return null;
        }

        return $fileName;
    }

    /**
     * Returns true for routes that starts with /s/.
     */
    private function isMauticAdministrationPage(): bool
    {
        return preg_match('/^\/s\//', $this->requestStack->getCurrentRequest()->getPathInfo()) >= 1;
    }
}
