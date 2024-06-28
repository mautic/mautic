<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Twig\Extension;

use Mautic\CampaignBundle\Twig\Extension\CampaignEventIconExtension;
use PHPUnit\Framework\TestCase;

final class CampaignEventIconExtensionTest extends TestCase
{
    private CampaignEventIconExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new CampaignEventIconExtension();
    }

    public function testGetCampaignEventIcon(): void
    {
        $eventTypes = [
            'lead.scorecontactscompanies' => 'ri-add-fill',
            'lead.addtocompany'           => 'ri-add-fill',
            'lead.changepoints'           => 'ri-edit-fill',
            'campaign.addremovelead'      => 'ri-edit-fill',
            'stage.change'                => 'ri-edit-fill',
            'lead.changelist'             => 'ri-edit-fill',
            'lead.changetags'             => 'ri-edit-fill',
            'lead.updatelead'             => 'ri-edit-fill',
            'lead.updatecompany'          => 'ri-edit-fill',
            'lead.changeowner'            => 'ri-edit-fill',
            'lead.deletecontact'          => 'ri-delete-bin-fill',
            'lead.adddnc'                 => 'ri-prohibited-fill',
            'lead.removednc'              => 'ri-close-fill',
            'campaign.sendwebhook'        => 'ri-webhook-fill',
            'email.send'                  => 'ri-mail-send-fill',
            'email.send.to.user'          => 'ri-mail-send-fill',
            'message.send'                => 'ri-send-plane-fill',
            'email.open'                  => 'ri-mail-open-fill',
            'email.click'                 => 'ri-cursor-fill',
            'email.reply'                 => 'ri-mail-unread-fill',
            'page.devicehit'              => 'ri-device-fill',
            'asset.download'              => 'ri-file-download-fill',
            'dwc.decision'                => 'ri-download-cloud-2-fill',
            'form.submit'                 => 'ri-survey-fill',
            'page.pagehit'                => 'ri-pages-fill',
            'lead.pageHit'                => 'ri-pages-fill',
            'lead.device'                 => 'ri-device-fill',
            'lead.field_value'            => 'ri-input-field',
            'lead.owner'                  => 'ri-user-2-fill',
            'lead.points'                 => 'ri-focus-2-fill',
            'lead.segments'               => 'ri-pie-chart-fill',
            'lead.stages'                 => 'ri-filter-fill',
            'lead.tags'                   => 'ri-hashtag',
            'notification.has.active'     => 'ri-notification-badge-fill',
            'email.validate.address'      => 'ri-mail-check-fill',
            'lead.dnc'                    => 'ri-prohibited-fill',
            'sms.reply'                   => 'ri-message-3-fill',
            'campaign.jump_to_event'      => 'ri-skip-forward-fill',
            'form.field_value'            => 'ri-input-field',
            'focus.show'                  => 'ri-slideshow-4-fill',
            'unknown.event.type'          => 'ri-shapes-fill',
        ];

        foreach ($eventTypes as $eventType => $expectedIcon) {
            $this->assertSame($expectedIcon, $this->extension->getCampaignEventIcon($eventType));
        }
    }
}
