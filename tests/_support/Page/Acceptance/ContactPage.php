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
    public static $ownerField         = '#lead_owner_chosen';

    // Form buttons (+New, Edit)
    public static $cancelButton       = '#lead_buttons_cancel_toolbar';
    public static $saveAndCloseButton = '#lead_buttons_save_toolbar';

    // Quick Add Form
    public static $quickAddModal      = '#MauticSharedModal-label';
    public static $saveButton         = '.btn-save.btn-copy';

    // Edit Contact Form
    public static $editForm     = '#core > div.pa-md.bdr-b > h4';

    // Delete Contact alert
    public static $ConfirmDelete = 'button.btn.btn-danger';

    // Contact Details Page
    public static $editButton = '#toolbar > div.std-toolbar > a:nth-child(1)';
    public static $dropDown   = 'button#core-options';
    public static $delete     = '#toolbar > div.std-toolbar.open > ul > li:nth-child(5) > a';

    // Contact Page
    public static $quickAddButton     = '.quickadd';
    public static $newContactButton   = '#new';

    // Import
    public static $chooseFileButton       = '#lead_import_file';
    public static $uploadButton           = '#lead_import_start';
    public static $importModal            = '#app-content > div > div.row.animation--slide-in-up > div > div > div.row > div > div';
    public static $importForm             = '#app-content > div > div.ml-lg.mr-lg.mt-md.pa-lg > form > div:nth-child(2) > div.panel-body';
    public static $importFormFields       = '#app-content > div > div.ml-lg.mr-lg.mt-md.pa-lg > form > div:nth-child(2) > div.panel-body > div:nth-child(1) > div > div > label';
    public static $firstName              = '#lead_field_import_firstname_chosen';
    public static $lastName               = '#lead_field_import_lastname_chosen';
    public static $email                  = '#lead_field_import_email_chosen';
    public static $company                = '#lead_field_import_company_chosen';
    public static $country                = '#lead_field_import_country_chosen';
    public static $importInBrowser        = '#lead_field_import_buttons_save_toolbar';
    public static $importProgressComplete = '#leadImportProgressComplete';

    // Campaigns
    public static $campaignsModalAddOption     = '//*[@id="lead_batch_add_chosen"]/ul/li/input';
    public static $campaignsModalRemoveOption  = '//*[@id="lead_batch_remove_chosen"]/ul/li/input';
    public static $firstCampaignFromAddList    = '#lead_batch_add_chosen > div > ul > li';
    public static $firstCampaignFromRemoveList = '#lead_batch_remove_chosen > div > ul > li';
    public static $campaignsModalSaveButton    = '#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-save.btn-copy';

    // Change Owner From
    public static $addToTheFollowing          = '#lead_batch_owner_addowner_chosen';
    public static $adminUser                  = '#lead_batch_owner_addowner_chosen > div > ul > li:nth-child(1)';
    public static $changeOwnerModalSaveButton = '//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[1]';

    // Change Segment Form
    public static $addToTheFollowingSegment           = '#lead_batch_add_chosen';
    public static $addToTheFollowingSegmentInput      = '#lead_batch_add_chosen > ul > li > input';
    public static $changeSegmentModalSaveButton       = '//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[1]';
    public static $removeFromTheFollowingSegment      = '#lead_batch_remove_chosen';
    public static $removeFromTheFollowingSegmentInput = '#lead_batch_remove_chosen > ul > li > input';

    // Search bar
    public static $searchBar   = '#list-search';
    public static $clearSearch = '#btn-filter';

    // Clear all selection button
    public static $clearAllContactsSelection = '#form-cancel';

    // Do Not Contact
    public static $firstContactDoNotContact  = '#leadTable > tbody > tr:nth-child(1) > td:nth-child(2) > a > div.pull-right > span';
    public static $secondContactDoNotContact = '#leadTable > tbody > tr:nth-child(2) > td:nth-child(2) > a > div.pull-right > span';
    public static $doNotContactSaveButton    = '//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[1]';

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
