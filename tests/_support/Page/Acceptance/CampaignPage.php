<?php

namespace Page\Acceptance;

class CampaignPage
{
    public const URL                              = '/s/campaigns';
    public const NEW_BUTTON                       = '#new';
    public const NAME_FIELD                       = '#campaign_name';
    public const SAVE_AND_CLOSE_BUTTON            = '#campaign_buttons_save_toolbar';
    public const CAMPAIGN_EVENT_MODAL_SAVE_BUTTON = '#CampaignEventModal .modal-form-buttons .btn-save.btn-copy';
    public const MODAL_SELECTOR                   = '#MauticSharedModal';
    public const CAMPAIGN_SOURCE_SEGMENT_PICKER   = '#campaign_leadsource_lists_chosen';
    public const CAMPAIGN_SOURCE_SEGMENT_SEARCH   = '#campaign_leadsource_lists_chosen .chosen-search-input';
    public const CAMPAIGN_SOURCE_SEGMENT_RESULTS  = '#campaign_leadsource_lists_chosen .chosen-results li.active-result';

    public static $URL                  = 's/campaigns/view/1';
    public static $contactsTab          = '//ul[contains(@class, "nav-tabs")]/li/a[@href="#leads-container"]';
    public static $contactsTabContainer = '#leads-container';
    public static $newButton            = '#new';
    public static $nameField            = '#campaign_name';
    public static $saveAndCloseButton   = '#campaign_buttons_save_toolbar';
    public static $campaignTable        = '#campaignTable';

    public static function route($param)
    {
        return self::URL.$param;
    }

    public static function editRoute(int $campaignId): string
    {
        return self::URL.'/edit/'.$campaignId;
    }

    public static function viewRoute(int $campaignId): string
    {
        return self::URL.'/view/'.$campaignId;
    }

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }
}
