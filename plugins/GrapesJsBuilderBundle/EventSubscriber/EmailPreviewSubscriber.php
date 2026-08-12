<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\GrapesJsBuilderBundle\Helper\MjmlContentHelper;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class EmailPreviewSubscriber implements EventSubscriberInterface
{
    private const ASSET_DIR             = 'plugins/GrapesJsBuilderBundle/Assets/library/js/dist';
    private const PREVIEW_SCRIPT_LOGICAL = 'mjml-preview.js';

    public function __construct(
        private Config $config,
        private Environment $twig,
        private CoreParametersHelper $coreParametersHelper,
        private string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_DISPLAY => ['wrapPublicMjmlPreview', -10000],
        ];
    }

    public function wrapPublicMjmlPreview(EmailSendEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $source = $event->getSource();

        if (!is_array($source) || empty($source['publicPreview'])) {
            return;
        }

        $content = $event->getContent(true);

        if (!MjmlContentHelper::isMjml($content)) {
            return;
        }

        $scriptUrl = $this->getPreviewScriptUrl();

        if (null === $scriptUrl) {
            return;
        }

        $event->setContent($this->twig->render(
            '@GrapesJsBuilder/Preview/mjml.html.twig',
            [
                'mjml'      => $content,
                'scriptUrl' => $scriptUrl,
            ]
        ));
    }

    private function getPreviewScriptUrl(): ?string
    {
        $scriptPath = $this->resolvePreviewScriptPath();

        if (null === $scriptPath) {
            return null;
        }

        $siteUrl = (string) $this->coreParametersHelper->get('site_url');

        return rtrim($siteUrl, '/').'/'.$scriptPath;
    }

    private function resolvePreviewScriptPath(): ?string
    {
        $assetDir     = $this->projectDir.'/'.self::ASSET_DIR;
        $manifestPath = $assetDir.'/manifest.json';

        if (!is_file($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (!is_array($manifest) || !isset($manifest[self::PREVIEW_SCRIPT_LOGICAL])) {
            return null;
        }

        $fileName = $manifest[self::PREVIEW_SCRIPT_LOGICAL];

        if (!is_string($fileName) || basename($fileName) !== $fileName || !is_file($assetDir.'/'.$fileName)) {
            return null;
        }

        return self::ASSET_DIR.'/'.$fileName;
    }
}
