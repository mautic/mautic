<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\RelationFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\RelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder;

class ContactSegmentFilterDictionary extends \ArrayIterator
{
    private $translations;

    public function __construct()
    {
        $this->translations['lead_email_read_count'] = [
            'type'                => RelationFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'email_stats',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'sum',
            'field'               => 'open_count',
            'null_value'          => 0,
        ];

        $this->translations['lead_email_received'] = [
            'type'                 => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table_field'  => 'lead_id',
            'foreign_table'        => 'email_stats',
            'field'                => 'email_id',
            'where'                => 'email_stats.is_read = 1',
        ];

        $this->translations['hit_url_count'] = [
            'type'                => RelationFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'page_hits',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'count',
            'field'               => 'id',
        ];

        $this->translations['lead_email_read_date'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'date_read',
        ];

        $this->translations['lead_email_sent_date'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'date_sent',
        ];

        $this->translations['hit_url_date'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'field'         => 'date_hit',
        ];

        $this->translations['dnc_bounced'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_bounced_sms'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_unsubscribed'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_manual_email'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_unsubscribed_sms'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['leadlist'] = [
            'type' => SegmentReferenceFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['globalcategory'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_categories',
            'field'         => 'category_id',
        ];

        $this->translations['tags'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_tags_xref',
            'field'         => 'tag_id',
        ];

        $this->translations['lead_email_sent'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'email_id',
        ];

        $this->translations['device_type'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device',
        ];

        $this->translations['device_brand'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_brand',
        ];

        $this->translations['device_os'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_os_name',
        ];

        $this->translations['device_model'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_model',
        ];

        $this->translations['stage'] = [
            'type'          => BaseFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'leads',
            'field'         => 'stage_id',
        ];

        $this->translations['notification'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'push_ids',
            'field'         => 'id',
        ];

        $this->translations['page_id'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'page_id',
        ];

        $this->translations['email_id'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'email_id',
        ];

        $this->translations['redirect_id'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'redirect_id',
        ];

        $this->translations['source'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'source',
        ];

        $this->translations['hit_url'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'field'         => 'url',
        ];

        $this->translations['referer'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['source_id'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['url_title'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['sessions'] = [
            'type' => SessionsFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['integration_campaigns'] = [
            'type' => IntegrationCampaignFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['utm_campaign'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];

        $this->translations['utm_content'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];

        $this->translations['utm_medium'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];

        $this->translations['utm_source'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];

        $this->translations['utm_term'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];

        $this->translations['campaign'] = [
            'type'          => RelationValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'campaign_leads',
            'field'         => 'campaign_id',
            'where'         => 'campaign_leads.manually_removed = 0',
        ];

        $this->translations['lead_asset_download'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'asset_downloads',
            'field'         => 'asset_id',
        ];

        parent::__construct($this->translations);
    }
}
