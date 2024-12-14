<?php

declare(strict_types=1);

namespace Page\Acceptance;

class EmailsPage
{
    public static $URL                       = '/s/emails';
    public static $NEW                       = '#new';
    public static $SUBJECT_FIELD             = 'emailform[subject]';
    public static $NEW_CATEGORY_OPTION       = '#email_batch_newCategory_chosen > div > ul > li.active-result:nth-child(1)';
    public static $NEW_CATEGORY_DROPDOWN     = '#email_batch_newCategory_chosen';
    public static $CHANGE_CATEGORY_ACTION    = "a[href='/s/emails/batch/categories/view']";
    public static $SELECTED_ACTIONS_DROPDOWN = '#page-list-wrapper > div.page-list > div.table-responsive > table > thead > tr > th.col-actions > div > div > button > i';
    public static $SAVE_BUTTON               = 'div.modal-form-buttons > button.btn.btn-primary.btn-save.btn-copy';
    public static $SELECT_ALL_CHECKBOX       = '#customcheckbox-one0';
    public static $SELECT_SEGMENT_EMAIL      = '#app-content > div > div.modal.fade.in.email-type-modal > div > div > div.modal-body.form-select-modal > div > div:nth-child(2) > div > div.hidden-xs.panel-footer.text-center > button';
    public static $CONTACT_SEGMENT_DROPDOWN  = '#emailform_lists_chosen';
    public static $CONTACT_SEGMENT_OPTION    = '#emailform_lists_chosen > div > ul > li';
    public static $SAVE_AND_CLOSE            = '#emailform_buttons_save_toolbar';
}
