<?php

/*
 * @copyright   2014-2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder;

/**
 * Class ContactSegmentFilterDictionary.
 *
 * @todo @petr Já jsem to myslím předělával už. Chtěl jsem z toho pak udělat i objekt, aby se člověk nemusel ptát na klíče v poli, ale pak jsme na to nesahali, protože to nebylo komplet
 */
class ContactSegmentFilterDictionary extends \ArrayIterator
{
    private $translations;

    public function __construct()
    {
        $this->translations['lead_email_read_count'] = [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'email_stats',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'sum',
            'field'               => 'open_count',
        ];

        $this->translations['lead_email_received'] = [
            'type'                 => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table_field'  => 'lead_id',
            'foreign_table'        => 'email_stats',
            'field'                => 'email_id',
            'where'                => 'email_stats.is_read = 1',
        ];

        $this->translations['hit_url_count'] = [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'page_hits',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'count',
            'field'               => 'id',
        ];

        $this->translations['lead_email_read_date'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'date_read',
        ];

        $this->translations['hit_url_date'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
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

        $this->translations['dnc_unsubscribed_sms'] = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['leadlist'] = [
            'type' => SegmentReferenceFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['globalcategory'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_categories',
            'field'         => 'category_id',
        ];

        $this->translations['tags'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_tags_xref',
            'field'         => 'tag_id',
        ];

        $this->translations['lead_email_sent'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'email_id',
        ];

        $this->translations['device_type'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device',
        ];

        $this->translations['device_brand'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_brand',
        ];

        $this->translations['device_os'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_os_name',
        ];

        $this->translations['device_model'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_model',
        ];

        $this->translations['stage'] = [
            'type'          => BaseFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'leads',
            'field'         => 'stage_id',
        ];

        $this->translations['notification'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'push_ids',
            'field'         => 'id',
        ];

        $this->translations['page_id'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'page_id',
        ];

        $this->translations['email_id'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'email_id',
        ];

        $this->translations['redirect_id'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'redirect_id',
        ];

        $this->translations['source'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'source',
        ];

        $this->translations['hit_url'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'field'         => 'url',
        ];

        $this->translations['referer'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['source_id'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['url_title'] = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];

        $this->translations['sessions'] = [
            'type' => SessionsFilterQueryBuilder::getServiceId(),
        ];

        parent::__construct($this->translations);
    }
}
