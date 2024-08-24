<?php

declare(strict_types=1);

namespace Page\Acceptance;

class CategoriesPage
{
    public const URL                 = '/s/categories';
    public const NEW_BUTTON          = '#new';
    public const BUNDLE_DROPDOWN     = '#category_form_bundle_chosen > a > span';
    public const BUNDLE_EMAIL_OPTION = '#category_form_bundle_chosen > div > ul > li.active-result:nth-child(4)';
    public const TITLE_FIELD         = 'category_form[title]';
    public const SAVE_AND_CLOSE      = '#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy';
}
