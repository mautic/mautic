<?php

declare(strict_types=1);

namespace Page\Acceptance;

class CategoriesPage
{
    public static $URL                 = '/s/categories';
    public static $NEW_BUTTON          = '#new';
    public static $BUNDLE_DROPDOWN     = '#category_form_bundle_chosen > a > span';
    public static $BUNDLE_EMAIL_OPTION = "#category_form_bundle_chosen > div > ul > li[data-option-array-index='3']";
    public static $TITLE_FIELD         = 'category_form[title]';
    public static $SAVE_AND_CLOSE      = 'div.modal-form-buttons button.btn.btn-primary.btn-save.btn-copy';
}
