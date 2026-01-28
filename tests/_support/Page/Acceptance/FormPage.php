<?php

declare(strict_types=1);

namespace Page\Acceptance;

class FormPage
{
    public static string $URL                                   = '/s/forms/new';
    public static string $FORM_TYPE                             = "//button[contains(@class, 'tile--clickable')]//h3[contains(text(), 'Standalone form')]/ancestor::button";

    public static string $FORM_NAME                             = 'Send Result';
    public static string $FORM_POST_ACTION_PROPERTY             = 'Thanks';
    public static string $ADD_NEW_FIELD_BUTTON_TEXT             = 'Add a new field';
    public static string $FORM_FIELD_TEXT_SHORT_ANSWER_SELECTOR = '//li[contains(text(), "Text: Short answer")]';
    public static string $FORM_FIELD_EMAIL_SELECTOR             = '//li[contains(text(), "Email")]';
}
