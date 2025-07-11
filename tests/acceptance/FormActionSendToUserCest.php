<?php

use Page\Acceptance\FormPage;
use Step\Acceptance\FormStep;

class FormActionSendToUserCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Login to Mautic
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createFormWithSendResultsAndToken(
        AcceptanceTester $I,
        FormStep $form,
    ): void {
        // Go to create new form
        $I->amOnPage(FormPage::$URL);

        // Select standalone form
        $form->selectAStandAloneType();

        // Fill basic form info
        $form->addFormMetaData();

        // Add First Name field
        $I->click('Fields');
        $I->waitForText('Add a new field', 3);

        // Add First Name field
        $form->createFormField(FormPage::$FORM_FIELD_TEXT_SHORT_ANSWER_SELECTOR, 'Text: Short answer', 'First Name');

        // Add Email Address field
        $form->createFormField(FormPage::$FORM_FIELD_EMAIL_SELECTOR, 'Email', 'Email Address');

        // Add "Send form results" action
        $I->click('Actions');

        $I->waitForText('Add a new submit action', 3);
        $I->click('Add a new submit action');
        $I->click('//li[contains(text(), "Send form results")]');
        $I->waitForText('Send form results', 2);

        // Assert token insertion
        $message = $I->grabValueFrom('#formaction_properties_message');
        $I->assertEquals(1, substr_count($message, '<strong>First Name</strong>: {formfield=first_name}'));
        $I->assertEquals(1, substr_count($message, '<strong>Email Address</strong>: {formfield=email_address}'));

        // Insert token manually and verify again
        $I->click("//div[@id='formFieldTokens']//a[contains(text(), 'First Name')]");
        $I->wait(1);

        $message = $I->grabValueFrom('#formaction_properties_message');
        $I->assertEquals(2, substr_count($message, '<strong>First Name</strong>: {formfield=first_name}'));
        $I->assertEquals(1, substr_count($message, '<strong>Email Address</strong>: {formfield=email_address}'));

        // Save the action
        $I->executeJS("document.querySelector('button[name=\"formaction[buttons][save]\"]').click();");
    }
}
