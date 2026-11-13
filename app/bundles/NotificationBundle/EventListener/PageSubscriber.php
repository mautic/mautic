<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\NotificationBundle\Helper\OneSignalIntegrationHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PageSubscriber
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private OneSignalIntegrationHelper $oneSignalIntegrationHelper,
    ) {
    }

    #[AsEventListener(
        event: PageDisplayEvent::class,
    )]
    public function onPageDisplay(): void
    {
        $features = $this->oneSignalIntegrationHelper->getIntegration()?->getIntegrationConfiguration()->getFeatureSettings() ?? [];
        $script = '';
        if (!in_array('landing_page_enabled', $features)) {
            $script = 'disable_notification = true;';
        }
        $this->assetsHelper->addScriptDeclaration($script, 'onPageDisplay_headClose');
    }
}
