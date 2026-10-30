<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Twig\Environment;

final readonly class CampaignInjectCustomContentSubscriber
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private Environment $twig,
    ) {
    }

    #[AsEventListener(
        event: CustomContentEvent::class,
    )]
    public function injectViewCustomContent(CustomContentEvent $customContentEvent): void
    {
        $parameters = $customContentEvent->getVars();
        $campaign   = $parameters['campaign'] ?? null;

        if (!$campaign instanceof Campaign) {
            return;
        }

        $viewName = '@MauticCampaign/Campaign/details.html.twig';
        if ($customContentEvent->checkContext($viewName, 'tabs')) {
            $content                   = $this->twig->render(
                '@MauticCampaign/Campaign/Tab/recent-activity-tab.html.twig',
            );
            $customContentEvent->addContent($content);
        } elseif ($customContentEvent->checkContext($viewName, 'tabs.content')) {
            $logs    = $this->auditLogModel->getLogForObject('campaign', $campaign->getId(), null, 100);
            $content = $this->twig->render(
                '@MauticCampaign/Campaign/Tab/recent-activity-tabcontent.html.twig',
                [
                    'campaign' => $campaign,
                    'logs'     => $logs,
                ]
            );
            $customContentEvent->addContent($content);
        }
    }
}
