<?php

namespace Step\Acceptance;

use Page\Acceptance\FormPage;

class FormStep extends \AcceptanceTester
{
    public function selectAStandAloneType(): void
    {
        $I = $this;
        $I->waitForText('What type of form do you want to create?', 10);
        $I->wait(1); // Give the modal time to fully render

        // Try to find and click the standalone form tile
        try {
            // First try clicking by selector
            $I->click(FormPage::$FORM_TYPE);
        } catch (\Exception $e) {
            // If that fails, try JavaScript approach
            $I->executeJS("Mautic.selectFormType('standalone');");
        }

        $I->see('New Form');
    }

    public function addFormMetaData(): void
    {
        $I = $this;
        // Fill Basic form info
        $I->fillField('mauticform[name]', FormPage::$FORM_NAME);
        $I->fillField('mauticform[postActionProperty]', FormPage::$FORM_POST_ACTION_PROPERTY);
    }

    public function createFormField(string $fieldType, string $modalHeader, string $label): void
    {
        $I = $this;
        $I->click(FormPage::$ADD_NEW_FIELD_BUTTON_TEXT);
        $I->click($fieldType);
        $I->waitForText($modalHeader, 2);
        $I->fillField('formfield[label]', $label);
        $I->click('div.modal-footer button.btn-primary');
        $I->wait(2);
    }
}
