<?php

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\Exception\FilterNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContactSegmentFilterDictionary
{
    /**
     * @var mixed[]
     */
    private $filters = [];

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return mixed[]
     */
    public function getFilters()
    {
        if (empty($this->filters)) {
            $this->setDefaultFilters();
            $this->fetchFiltersFromSubscribers();
        }

        return $this->filters;
    }

    /**
     * @param string $filterKey
     *
     * @return mixed[]
     *
     * @throws FilterNotFoundException
     */
    public function getFilter($filterKey)
    {
        if (array_key_exists($filterKey, $this->getFilters())) {
            return $this->filters[$filterKey];
        }

        throw new FilterNotFoundException("Filter '{$filterKey}' does not exist");
    }

    /**
     * @param string $filterKey
     * @param string $property
     *
     * @return string|int
     *
     * @throws FilterNotFoundException
     */
    public function getFilterProperty($filterKey, $property)
    {
        $filter = $this->getFilter($filterKey);

        if (array_key_exists($property, $filter)) {
            return $filter[$property];
        }

        throw new FilterNotFoundException("Filter '{$filterKey}' does not have property '{$property}' exist");
    }

    private function setDefaultFilters(): void
    {
        $this->filters['lead_email_read_count']         = [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'email_stats',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'sum',
            'field'               => 'open_count',
            'null_value'          => 0,
        ];
        $this->filters['lead_email_received']           = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table_field' => 'lead_id',
            'foreign_table'       => 'email_stats',
            'field'               => 'email_id',
            'where'               => 'email_stats.is_read = 1',
        ];
        $this->filters['hit_url_count']                 = [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'page_hits',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'count',
            'field'               => 'id',
        ];
        $this->filters['lead_email_read_date']          = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'date_read',
        ];
        $this->filters['lead_email_sent_date']          = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'date_sent',
        ];
        $this->filters['hit_url_date']                  = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'field'         => 'date_hit',
        ];
        $this->filters['dnc_bounced']                   = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['dnc_bounced_sms']               = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['dnc_unsubscribed']              = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['dnc_manual_email']     = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['dnc_unsubscribed_sms']          = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['dnc_manual_sms']     = [
            'type' => DoNotContactFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['leadlist']                      = [
            'type' => SegmentReferenceFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['globalcategory']                = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_categories',
            'field'         => 'category_id',
        ];
        $this->filters['tags']                          = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_tags_xref',
            'field'         => 'tag_id',
        ];
        $this->filters['lead_email_sent']               = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'email_stats',
            'field'         => 'email_id',
        ];
        $this->filters['device_type']                   = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device',
        ];
        $this->filters['device_brand']                  = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_brand',
        ];
        $this->filters['device_os']                     = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_os_name',
        ];
        $this->filters['device_model']                  = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_devices',
            'field'         => 'device_model',
        ];
        $this->filters['stage']                         = [
            'type'          => BaseFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'leads',
            'field'         => 'stage_id',
        ];
        $this->filters['notification']                  = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'push_ids',
            'field'         => 'id',
        ];
        $this->filters['page_id']                       = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'page_id',
        ];
        $this->filters['redirect_id']                   = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'redirect_id',
        ];
        $this->filters['source']                        = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'foreign_field' => 'source',
        ];
        $this->filters['hit_url']                       = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
            'field'         => 'url',
        ];
        $this->filters['referer']                       = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];
        $this->filters['source_id']                     = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];
        $this->filters['url_title']                     = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'page_hits',
        ];
        $this->filters['email_id'] = [ // kept as email_id for BC
            'type' => ChannelClickQueryBuilder::getServiceId(),
        ];
        $this->filters['email_clicked_link_date'] = [
            'type' => ChannelClickQueryBuilder::getServiceId(),
        ];
        $this->filters['sms_clicked_link'] = [
            'type'  => ChannelClickQueryBuilder::getServiceId(),
        ];
        $this->filters['sms_clicked_link_date'] = [
            'type'  => ChannelClickQueryBuilder::getServiceId(),
        ];
        $this->filters['sessions']              = [
            'type' => SessionsFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['integration_campaigns'] = [
            'type' => IntegrationCampaignFilterQueryBuilder::getServiceId(),
        ];
        $this->filters['utm_campaign']          = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];
        $this->filters['utm_content']           = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];
        $this->filters['utm_medium']            = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];
        $this->filters['utm_source']            = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];
        $this->filters['utm_term']              = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'lead_utmtags',
        ];
        $this->filters['campaign']              = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'campaign_leads',
            'field'         => 'campaign_id',
            'where'         => 'campaign_leads.manually_removed = 0',
        ];
        $this->filters['lead_asset_download']   = [
            'type'          => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table' => 'asset_downloads',
            'field'         => 'asset_id',
        ];
    }

    /**
     * Other bundles can add more filters by subscribing to this event.
     */
    private function fetchFiltersFromSubscribers(): void
    {
        if ($this->dispatcher->hasListeners(LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE)) {
            $event = new SegmentDictionaryGenerationEvent($this->filters);
            $this->dispatcher->dispatch(LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE, $event);
            $this->filters = $event->getTranslations();
        }
    }
}
