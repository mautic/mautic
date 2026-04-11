<?php

declare(strict_types=1);

namespace Page\Acceptance;

class EmailsPage
{
    public const URL                         = '/s/emails';
    public const NEW_BUTTON                  = 'a#new';
    public const EDIT_BUTTON                 = 'a#edit';
    public const SCHEDULE_BUTTON             = 'a[aria-label="Schedule"][data-header="Schedule"]';
    public const SUBJECT_FIELD               = 'emailform[subject]';
    public const NEW_CATEGORY_OPTION         = '#email_batch_newCategory_chosen > div > ul > li.active-result:nth-child(1)';
    public const NEW_CATEGORY_DROPDOWN       = '#email_batch_newCategory_chosen';
    public const CHANGE_CATEGORY_ACTION      = "a[href='/s/emails/batch/categories/view']";
    public const SELECTED_ACTIONS_DROPDOWN   = 'div.toolbar--action-list > div > button#core-options';
    public const SAVE_BUTTON                 = 'div.modal-form-buttons > button.btn.btn-primary.btn-save.btn-copy';
    public const SELECT_ALL_CHECKBOX         = '#customcheckbox-one0';
    public const SELECT_TRIGGERED_EMAIL      = '.modal.fade.in.email-type-modal .col-md-6:first-child .tile.tile--clickable';
    public const SELECT_SEGMENT_EMAIL        = '.modal.fade.in.email-type-modal .col-md-6:last-child .tile.tile--clickable';
    public const CONTACT_SEGMENT_DROPDOWN    = '#emailform_lists_chosen';
    public const CONTACT_SEGMENT_OPTION      = '#emailform_lists_chosen > div > ul > li';
    public const SAVE_AND_CLOSE              = '#emailform_buttons_save_toolbar';
    public const EMAIL_TYPE_MODAL            = '.email-type-modal';
}
