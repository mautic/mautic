<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CampaignEventIconExtension extends AbstractExtension
{
    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getCampaignEventIcon', [$this, 'getCampaignEventIcon']),
        ];
    }

    public function getCampaignEventIcon(string $eventType): string
    {
        return match ($eventType) {
            'lead.adddnc', 'lead.scorecontactscompanies', 'lead.addtocompany' => 'ri-add-line',
            'lead.changepoints', 'campaign.addremovelead', 'stage.change', 'lead.changelist', 'lead.changetags', 'lead.updatelead', 'lead.updatecompany', 'lead.changeowner' => 'ri-edit-line',
            'lead.deletecontact' => 'ri-delete-bin-line',
            'lead.removednc' => 'ri-close-line',
            'campaign.sendwebhook' => 'ri-webhook-line',
            'email.send', 'email.send.to.user' => 'ri-mail-send-line',
            'message.send' => 'ri-send-plane-line',
            'email.open' => 'ri-mail-open-line',
            'email.click' => 'ri-cursor-line',
            'email.reply' => 'ri-mail-unread-line',
            'page.devicehit' => 'ri-device-line',
            'asset.download' => 'ri-file-download-line',
            'dwc.decision' => 'ri-download-cloud-2-line',
            'form.submit' => 'ri-survey-line',
            'page.pagehit', 'lead.pageHit' => 'ri-pages-line',
            'lead.device' => 'ri-device-line',
            'lead.field_value' => 'ri-input-field',
            'lead.owner' => 'ri-user-2-line',
            'lead.points' => 'ri-focus-2-line',
            'lead.segments' => 'ri-pie-chart-line',
            'lead.stages' => 'ri-filter-line',
            'lead.tags' => 'ri-hashtag',
            'notification.has.active' => 'ri-notification-badge-line',
            'email.validate.address' => 'ri-mail-check-line',
            'lead.dnc' => 'ri-prohibited-line',
            default => 'ri-shapes-line',
        };
    }
}
