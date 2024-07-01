<?php

namespace Page\Acceptance;

class ContactPage
{
    public static $URL = '/s/contacts';

    public static $quickAddButton     = '#toolbar .quickadd';
    public static $quickAddModal      = '#MauticSharedModal-label';
    public static $firstNameField     = '#lead_firstname';
    public static $lastNameField      = '#lead_lastname';
    public static $emailField         = '#lead_email';
    public static $tagField           = '#lead_tags_chosen input';
    public static $saveButton         = '.btn-save.btn-copy';
    public static $listSearch         = '#list-search';
    public static $listTable          = '#leadTable';
    public static $clearSearch        = '#btn-filter > i';
    public static $newContactButton   = '#toolbar a:nth-child(2)';
    public static $saveAndCloseButton = '#lead_buttons_save_toolbar';
    public static $contactDetailsPage = '#app-content > div > div.page-header';
    public static $closeButton        = '#toolbar > div.std-toolbar.btn-group > a:nth-child(3)';
    public static $editButton         = '#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(1) > a';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');.
     */
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
