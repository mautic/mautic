<?php

declare(strict_types=1);

namespace Page\Acceptance;

class EmailsPage
{
    public const URL                       = '/s/emails';
    public const NEW                       = '#new';
    public const SUBJECT_FIELD             = 'emailform[subject]';
    public const NEW_CATEGORY_OPTION       = '#email_batch_newCategory_chosen > div > ul > li.active-result:nth-child(1)';
    public const NEW_CATEGORY_DROPDOWN     = '#email_batch_newCategory_chosen';
    public const CHANGE_CATEGORY_ACTION    = "a[href='/s/emails/batch/categories/view']";
    public const SELECTED_ACTIONS_DROPDOWN = '#app-content > div > div.panel.panel-default.bdr-t-wdh-0.mb-0 > div.page-list > div.table-responsive > table > thead > tr > th.col-actions > div > div > button > i';
    public const SAVE_BUTTON               = '#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy';
    public const SELECT_ALL_CHECKBOX       = '#customcheckbox-one0';
    public const SELECT_SEGMENT_EMAIL      = '#app-content > div > div.modal.fade.in.email-type-modal > div > div > div.modal-body.form-select-modal > div > div:nth-child(2) > div > div.hidden-xs.panel-footer.text-center > button';
    public const CONTACT_SEGMENT_DROPDOWN  = '#emailform_lists_chosen';
    public const CONTACT_SEGMENT_OPTION    = '#emailform_lists_chosen > div > ul > li';
    public const SAVE_AND_CLOSE            = '#emailform_buttons_save_toolbar';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');.
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    protected \AcceptanceTester $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public static function buildSelectorForCategory(int $number): string
    {
        return '#email_batch_add_chosen > div.chosen-drop > ul.chosen-results > li.active-result';
    }
}
