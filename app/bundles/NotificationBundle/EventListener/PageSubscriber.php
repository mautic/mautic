<?php

namespace Mautic\NotificationBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;

final readonly class PageSubscriber
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private IntegrationHelper $integrationHelper,
    ) {
    }

    #[AsEventListener(event: PageDisplayEvent::class)]
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
