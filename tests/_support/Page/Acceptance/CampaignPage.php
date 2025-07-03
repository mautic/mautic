<?php

namespace Page\Acceptance;

class CampaignPage
{
    public static $URL = 's/campaigns/view/1';

    public static $contactsTab                  = '//*[@id="app-content"]/div/div[2]/div[1]/div[2]/div[4]/ul/li[last()-1]/a';
    public static $firstContactFromContactsTab  = '#leads-container > div.pa-md > div > div:nth-child(1) > div';
    public static $secondContactFromContactsTab = '#leads-container > div.pa-md > div > div:nth-child(2) > div';

    public static function route($param)
    {
        return static::$URL.$param;
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
