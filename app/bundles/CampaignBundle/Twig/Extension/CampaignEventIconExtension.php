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
            'lead.scorecontactscompanies', 'lead.addtocompany' => 'ri-add-fill',
            'lead.changepoints', 'campaign.addremovelead', 'stage.change', 'lead.changelist', 'lead.changetags', 'lead.updatelead', 'lead.updatecompany', 'lead.changeowner' => 'ri-edit-fill',
            'lead.deletecontact'   => 'ri-delete-bin-fill',
            'lead.adddnc'          => 'ri-prohibited-fill',
            'lead.removednc'       => 'ri-close-fill',
            'campaign.sendwebhook' => 'ri-webhook-fill',
            'email.send', 'email.send.to.user' => 'ri-mail-send-fill',
            'message.send'   => 'ri-send-plane-fill',
            'email.open'     => 'ri-mail-open-fill',
            'email.click'    => 'ri-cursor-fill',
            'email.reply'    => 'ri-mail-unread-fill',
            'page.devicehit' => 'ri-device-fill',
            'asset.download' => 'ri-file-download-fill',
            'dwc.decision'   => 'ri-download-cloud-2-fill',
            'form.submit'    => 'ri-survey-fill',
            'page.pagehit', 'lead.pageHit' => 'ri-pages-fill',
            'lead.device'             => 'ri-device-fill',
            'lead.field_value'        => 'ri-input-field',
            'lead.owner'              => 'ri-user-2-fill',
            'lead.points'             => 'ri-focus-2-fill',
            'lead.segments'           => 'ri-pie-chart-fill',
            'lead.stages'             => 'ri-filter-fill',
            'lead.tags'               => 'ri-hashtag',
            'notification.has.active' => 'ri-notification-badge-fill',
            'email.validate.address'  => 'ri-mail-check-fill',
            'lead.dnc'                => 'ri-prohibited-fill',
            'sms.reply'               => 'ri-message-3-fill',
            'campaign.jump_to_event'  => 'ri-skip-forward-fill',
            'form.field_value'        => 'ri-input-field',
            'focus.show'              => 'ri-slideshow-4-fill',
            default                   => 'ri-shapes-fill',
        };
    }
}
