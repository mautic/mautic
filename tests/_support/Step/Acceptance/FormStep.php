<?php

namespace Step\Acceptance;

use Page\Acceptance\FormPage;

class FormStep extends \AcceptanceTester
{
    public function addFormMetaData(): void
    {
        $I = $this;
        // Fill Basic form info
        $I->fillField('mauticform[name]', FormPage::$FORM_NAME);
        $I->fillField('mauticform[postActionProperty]', FormPage::$FORM_POST_ACTION_PROPERTY);
    }

    public function createFormField(
        string $fieldType,
        string $modalHeader,
        string $label,
        ?string $labelSelector = null,
        ?string $saveButtonSelector = null,
    ): void {
        $I = $this;
        $labelSelector ??= FormPage::$FORM_FIELD_LABEL_SELECTOR;
        $saveButtonSelector ??= FormPage::$FORM_FIELD_SAVE_BUTTON_SELECTOR;

        $I->click(FormPage::$ADD_NEW_FIELD_BUTTON_TEXT);
        $I->click($fieldType);
        $I->waitForText($modalHeader, 5);
        $I->waitForElementVisible($labelSelector, 10);
        $I->fillField($labelSelector, $label);
        $I->waitForElementClickable($saveButtonSelector, 10);
        $I->click($saveButtonSelector);
        $I->waitForElementNotVisible($labelSelector, 10); // modal closed
    }
}
