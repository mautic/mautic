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

use Mautic\LeadBundle\Segment\FilterQueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\DncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\LeadListFilterQueryBuilder;

class LeadSegmentFilterDescriptor extends \ArrayIterator
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

        $this->translations['lead_email_read_date'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'page_hits',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'date_hit',
        ];

        $this->translations['dnc_bounced'] = [
            'type'                => DncFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_bounced_sms'] = [
            'type'                => DncFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['leadlist'] = [
            'type'                => LeadListFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['globalcategory'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_categories',
            'field'               => 'category_id',
        ];

        $this->translations['tags'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_tags_xref',
            'field'               => 'tag_id',
        ];

        $this->translations['lead_email_sent'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'email_stats',
            'field'               => 'email_id',
        ];

        $this->translations['device_type'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_devices',
            'field'               => 'device',
        ];

        $this->translations['device_brand'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_devices',
            'field'               => 'device_brand',
        ];

        $this->translations['device_os'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_devices',
            'field'               => 'device_os_name',
        ];

        $this->translations['device_model'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'lead_devices',
            'field'               => 'device_model',
        ];

        $this->translations['stage'] = [
            'type'                => BaseFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'leads',
            'field'               => 'stage_id',
        ];

        parent::__construct($this->translations);
    }
}
