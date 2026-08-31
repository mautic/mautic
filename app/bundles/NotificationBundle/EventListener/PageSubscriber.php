<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageDisplayEvent::class => ['onPageDisplay', 0],
        ];
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('OneSignal');
        $settings          = $integrationObject->getIntegrationSettings();
        $features          = $settings->getFeatureSettings();

        $script = '';
        if (!in_array('landing_page_enabled', $features)) {
            $script = 'disable_notification = true;';
        }

        $this->assetsHelper->addScriptDeclaration($script, 'onPageDisplay_headClose');
    }
}
