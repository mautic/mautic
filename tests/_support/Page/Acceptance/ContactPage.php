<?php

namespace Page\Acceptance;

class ContactPage
{
    public static $URL = '/s/contacts';

    // Form fields
    public static $firstNameField     = '#lead_firstname';
    public static $lastNameField      = '#lead_lastname';
    public static $emailField         = '#lead_email';
    public static $tagField           = '#lead_tags_chosen input';

    // Form buttons (+New, Edit)
    public static $cancelButton       = '#lead_buttons_cancel_toolbar';
    public static $saveAndCloseButton = '#lead_buttons_save_toolbar';

    // Quick Add Form
    public static $quickAddButton     = '#toolbar .quickadd';
    public static $quickAddModal      = '#MauticSharedModal-label';
    public static $saveButton         = '.btn-save.btn-copy';

    // +New Contact Form
    public static $newContactButton   = '#toolbar a:nth-child(2)';

    // Edit Contact Form
    public static $editForm     = '#core > div.pa-md.bg-light-xs.bdr-b > h4';

    // Contact Details Page
    public static $editButton = '#toolbar > div.std-toolbar.btn-group > a:nth-child(1)';

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
