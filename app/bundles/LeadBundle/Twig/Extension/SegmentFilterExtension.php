<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SegmentFilterExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSegmentFilterIcon', [$this, 'getSegmentFilterIcon']),
        ];
    }

    public function getSegmentFilterIcon(string $filterType, string $objectType = ''): string
    {
        $icon =  match ($filterType) {
            // lead
            'address1'               => '',
            'address2'               => '',
            'attribution'            => '',
            'attribution_date'       => '',
            'dnc_bounced'            => '',
            'dnc_bounced_sms'        => '',
            'campaign'               => '',
            'city'                   => '',
            'country'                => '',
            'date_added'             => '',
            'date_identified'        => '',
            'last_active'            => '',
            'device_brand'           => '',
            'device_model'           => '',
            'device_os'              => '',
            'device_type'            => '',
            'email'                  => '',
            'generated_email_domain' => '',
            'facebook'               => '',
            'fax'                    => '',
            'firstname'              => '',
            'foursquare'             => '',
            'instagram'              => '',
            'lastname'               => '',
            'mobile'                 => '',
            'date_modified'          => '',
            'owner_id'               => '',
            'phone'                  => '',
            'points'                 => '',
            'position'               => '',
            'preferred_locale'       => '',
            'timezone'               => '',
            'company'                => '',
            'leadlist'               => '',
            'skype'                  => '',
            'stage'                  => '',
            'state'                  => '',
            'globalcategory'         => '',
            'tags'                   => '',
            'title'                  => '',
            'twitter'                => '',
            'utm_campaign'           => '',
            'utm_content'            => '',
            'utm_medium'             => '',
            'utm_source'             => '',
            'utm_term'               => '',
            'dnc_unsubscribed'       => '',
            'dnc_unsubscribed_sms'   => '',
            'dnc_manual_email'       => '',
            'dnc_manual_sms'         => '',
            'website'                => '',
            'zipcode'                => '',
            'linkedin'               => '',

            // company
            'companyaddress1'            => '',
            'companyaddress2'            => '',
            'companyannual_revenue'      => '',
            'companycity'                => '',
            'companyemail'               => '',
            'companyname'                => '',
            'companycountry'             => '',
            'companydescription'         => '',
            'companyfax'                 => '',
            'companyindustry'            => '',
            'companynumber_of_employees' => '',
            'companyphone'               => '',
            'companystate'               => '',
            'companywebsite'             => '',
            'companyzipcode'             => '',

            // behaviors
            'redirect_id'             => '',
            'email_id'                => '',
            'email_clicked_link_date' => '',
            'sms_clicked_link'        => '',
            'sms_clicked_link_date'   => '',
            'lead_asset_download'     => '',
            'sessions'                => '',
            'notification'            => '',
            'lead_email_received'     => '',
            'lead_email_read_date'    => '',
            'lead_email_read_count'   => '',
            'lead_email_sent_date'    => '',
            'hit_url'                 => '',
            'page_id'                 => '',
            'hit_url_date'            => '',
            'hit_url_count'           => '',
            'referer'                 => '',
            'source'                  => '',
            'source_id'               => '',
            'url_title'               => '',
            'lead_email_sent'         => '',
            default                   => '',
        };

        if (!empty($icon)) {
            return $icon;
        }

        return match ($objectType) {
            'company'  => 'ri-building-2-line',
            'lead'     => 'ri-user-6-fill',
            default    => '',
        };
    }
}
