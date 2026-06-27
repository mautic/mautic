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
    private const PREVIEW_SCRIPT_PATH = 'plugins/GrapesJsBuilderBundle/Assets/library/js/dist/mjml-preview.js';

    public function __construct(
        private Config $config,
        private Environment $twig,
        private CoreParametersHelper $coreParametersHelper,
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

        $event->setContent($this->twig->render(
            '@GrapesJsBuilder/Preview/mjml.html.twig',
            [
                'mjml'      => $content,
                'scriptUrl' => $this->getPreviewScriptUrl(),
            ]
        ));
    }

    private function getPreviewScriptUrl(): string
    {
        $siteUrl = (string) $this->coreParametersHelper->get('site_url');

        return rtrim($siteUrl, '/').'/'.self::PREVIEW_SCRIPT_PATH;
    }
}
