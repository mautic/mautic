<?php

declare(strict_types=1);

namespace Page\Acceptance;

class CategoriesPage
{
    public const URL                 = '/s/categories';
    public const NEW_BUTTON          = '#new';
    public const BUNDLE_DROPDOWN     = '#category_form_bundle_chosen > a > span';
    public const BUNDLE_EMAIL_OPTION = "#category_form_bundle_chosen > div > ul > li[data-option-array-index='3']";
    public const TITLE_FIELD         = 'category_form[title]';
    public const SAVE_AND_CLOSE      = 'div.modal-form-buttons button.btn.btn-primary.btn-save.btn-copy';
}
