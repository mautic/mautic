<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\ContactPage;

class ContactStep extends \AcceptanceTester
{
    /**
     * Fill out the contact form with the provided details.
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $tag
     */
    public function fillContactForm($firstName, $lastName, $email, $tag): void
    {
        $I = $this;
        // Wait for the first name field to be visible
        $I->waitForElementVisible(ContactPage::$firstNameField, 10);
        // Fill in the form fields
        $I->fillField(ContactPage::$firstNameField, $firstName);
        $I->fillField(ContactPage::$lastNameField, $lastName);
        $I->fillField(ContactPage::$emailField, $email);
        $I->fillField(ContactPage::$tagField, $tag);
        $I->pressKey(ContactPage::$tagField, WebDriverKeys::ENTER);
        // // Set the owner to "Sales User"
        $I->click(ContactPage::$ownerField.'> a > span');
        $I->fillField(ContactPage::$ownerField.' > div > div > input', 'Sales');
        $I->pressKey(ContactPage::$ownerField.' > div > div > input', WebDriverKeys::ENTER);
    }

    /**
     * Grab the name of a contact from the contact list.
     *
     * @return string the name of the contact
     */
    public function grabContactNameFromList($place)
    {
        $I           = $this;
        $xpath       = "//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]";
        $contactName = $I->grabTextFrom($xpath);
        $I->see($contactName, $xpath);

        return $contactName;
    }

    /**
     * Select an option from the dropdown menu for a specific contact.
     */
    public function selectOptionFromDropDown($place, $option): void
    {
        $I = $this;
        // Click the dropdown menu
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/button");
        // Select the desired option
        $I->waitForElementClickable("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a", 30);
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a");
    }

    /**
     * Select a contact from the contact list.
     */
    public function selectContactFromList($place): void
    {
        $I     = $this;
        $xpath = "//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/span/input";
        $I->waitForElementClickable($xpath, 10);
        $I->checkOption($xpath);
        $I->seeCheckboxIsChecked($xpath);
    }

    /**
     * Select an option from the dropdown menu for multiple selected contacts.
     */
    public function selectOptionFromDropDownForMultipleSelections($option)
    {
        $I = $this;
        // Click the dropdown button for bulk actions
        $xpathDropdownButton = '//*[@id="leadTable"]/thead/tr/th[1]/div/div/button/i';
        $I->waitForElementClickable($xpathDropdownButton, 10);
        $I->click($xpathDropdownButton);
        // Select the desired option from the dropdown menu
        $xpathOption = "//*[@id='leadTable']/thead/tr/th[1]/div/div/ul/li[$option]/a/span";
        $I->waitForElementClickable($xpathOption, 10);
        $I->click($xpathOption);
    }

    /**
     * Select an option from the dropdown menu (beside the Quick Add, +New button) on the contacts page.
     *
     * @param int $option the option to select (1-> Export to CSV, 2-> Export to Excel, 3-> Import, 4-> Import History)
     */
    public function selectOptionFromDropDownContactsPage($option): void
    {
        $I = $this;
        $I->click("//*[@id='page-list-actions']");
        $I->click("//*[@id='page-list-wrapper']/div[1]/div/div[2]/ul/li[$option]/a");
    }

    /**
     * Fill out the import form fields with specified placeholder data.
     */
    public function fillImportFormFields(): void
    {
        $I = $this;
        // Fill out the first name field in the import form
        $I->click(ContactPage::$firstName.'> a > span');
        $I->fillField(ContactPage::$firstName.' > div > div > input', 'first name');
        $I->pressKey(ContactPage::$firstName.' > div > div > input', WebDriverKeys::ENTER);
        // Fill out the last name field in the import form
        $I->click(ContactPage::$lastName.'> a > span');
        $I->fillField(ContactPage::$lastName.' > div > div > input', 'last name');
        $I->pressKey(ContactPage::$lastName.' > div > div > input', WebDriverKeys::ENTER);
        // Fill out the email field in the import form
        $I->click(ContactPage::$email.'> a > span');
        $I->fillField(ContactPage::$email.' > div > div > input', 'email');
        $I->pressKey(ContactPage::$email.' > div > div > input', WebDriverKeys::ENTER);
        // Fill out the company field in the import form
        $I->click(ContactPage::$company.'> a > span');
        $I->fillField(ContactPage::$company.' > div > div > input', 'company name');
        $I->pressKey(ContactPage::$company.' > div > div > input', WebDriverKeys::ENTER);
        // Fill out the country field in the import form
        $I->click(ContactPage::$country.'> a > span');
        $I->fillField(ContactPage::$country.' > div > div > input', 'country');
        $I->pressKey(ContactPage::$country.' > div > div > input', WebDriverKeys::ENTER);
    }

    /**
     * Check the owner of a specific contact.
     *
     * @param int $place the position of the contact in the list (starting from 1)
     */
    public function checkOwner($place): void
    {
        $I = $this;
        // Navigate to the contacts page
        $I->amOnPage(ContactPage::$URL);
        // Grab the contact's name and navigate to their details page
        $contactName = $I->grabTextFrom("//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]");
        $I->click(['link' => $contactName]);
        // Wait for the contact's name to appear on the details page
        $I->waitForText($contactName, 10, '#app-content');
        // Verify the owner is "Sales User"
        $I->waitForElement('//*[@id="app-content"]/div/div[2]/div[2]/div[1]/div[4]/p[1]', 15);
        $I->see('Sales User', '//*[@id="app-content"]/div/div[2]/div[2]/div[1]/div[4]/p[1]');
    }

    /**
     * Verify that the owner of a contact has changed to "Admin User".
     *
     * @param int $place the position of the contact in the list (starting from 1)
     */
    public function verifyOwner($place): void
    {
        $I = $this;
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');
        // Grab the contact's name and navigate to their details page
        $contactName = $I->grabTextFrom("//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]");
        $I->click(['link' => $contactName]);
        // Wait for the contact's name to appear on the details page
        $I->waitForText($contactName, 10, '#app-content');
        // Verify the owner is "Admin User"
        $I->waitForElement('//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]', 15);
        $I->see('Admin User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]');
    }
}
