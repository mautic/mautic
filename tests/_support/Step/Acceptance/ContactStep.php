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
    public function fillContactForm($firstName, $lastName, $email, $tag)
    {
        $I = $this;
        $I->waitForElementVisible(ContactPage::$firstNameField, 10);
        $I->fillField(ContactPage::$firstNameField, $firstName);
        $I->fillField(ContactPage::$lastNameField, $lastName);
        $I->fillField(ContactPage::$emailField, $email);
        $I->fillField(ContactPage::$tagField, $tag);
        $I->pressKey(ContactPage::$tagField, WebDriverKeys::ENTER);
        $I->click(ContactPage::$ownerField.'> a > span');
        $I->fillField(ContactPage::$ownerField.' > div > div > input', 'Sales');
        $I->pressKey(ContactPage::$ownerField.' > div > div > input', WebDriverKeys::ENTER);
    }

    /**
     * Grab the name of a contact from the contact list.
     *
     * @param int $place the position of the contact in the list (starting from 1)
     *
     * @return string
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
     *
     * @param int $place  the position of the contact in the list (starting from 1)
     * @param int $option the option to select (1-> Edit, 2-> Details, 3-> Send Email, 4-> Delete)
     */
    public function selectOptionFromDropDown($place, $option)
    {
        // Option: 1-> Edit, 2-> Details, 3-> Send Email, 4-> Delete
        $I = $this;
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/button");
        $I->waitForElementClickable("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a", 30);
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a");
    }

    /**
     * Select a contact from the contact list.
     *
     * @param int $place the position of the contact in the list (starting from 1)
     */
    public function selectContactFromList($place)
    {
        $I = $this;
        $I->checkOption("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/span/input");
    }

    /**
     * Select an option from the dropdown menu for multiple selected contacts.
     *
     * @param int $option The option to select (e.g., 11 for batch delete).
     */
    public function selectOptionFromDropDownForMultipleSelections($option)
    {
        $I = $this;
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button/i');
        $I->click("//*[@id='leadTable']/thead/tr/th[1]/div/div/ul/li[$option]/a/span/span");
    }

    public function selectOptionFromDropDownContactsPage($option)
    {
        $I = $this;
        $I->click('#toolbar > div.std-toolbar.btn-group > button');
        $I->click("//*[@id='toolbar']/div[1]/ul/li[$option]/a/span/span");
    }

    public function fillImportFormFields()
    {
        $I = $this;
        $I->click(ContactPage::$firstName.'> a > span');
        $I->fillField(ContactPage::$firstName.' > div > div > input', 'first name');
        $I->pressKey(ContactPage::$firstName.' > div > div > input', WebDriverKeys::ENTER);

        $I->click(ContactPage::$lastName.'> a > span');
        $I->fillField(ContactPage::$lastName.' > div > div > input', 'last name');
        $I->pressKey(ContactPage::$lastName.' > div > div > input', WebDriverKeys::ENTER);

        $I->click(ContactPage::$email.'> a > span');
        $I->fillField(ContactPage::$email.' > div > div > input', 'email');
        $I->pressKey(ContactPage::$email.' > div > div > input', WebDriverKeys::ENTER);

        $I->click(ContactPage::$company.'> a > span');
        $I->fillField(ContactPage::$company.' > div > div > input', 'company name');
        $I->pressKey(ContactPage::$company.' > div > div > input', WebDriverKeys::ENTER);

        $I->click(ContactPage::$country.'> a > span');
        $I->fillField(ContactPage::$country.' > div > div > input', 'country');
        $I->pressKey(ContactPage::$country.' > div > div > input', WebDriverKeys::ENTER);
    }

    public function checkOwner($place)
    {
        $I = $this;
        $I->amOnPage(ContactPage::$URL);
        $contactName=$I->grabTextFrom("//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]");
        $I->click("#leadTable > tbody > tr:nth-child($place) > td:nth-child(2) > a > div");
        $I->waitForText($contactName, 10, '#app-content > div > div.page-header > div > div.col-xs-5.col-sm-6.col-md-5.va-m > h1 > div > span:nth-child(1)');
        $I->see('Sales User', '#app-content > div > div.box-layout > div.col-md-3.bdr-l.height-auto > div.panel.bg-transparent.shd-none.bdr-rds-0.bdr-w-0.mb-0 > div.panel-body.pt-sm > p:nth-child(2)');
    }

    public function verifyOwner($place)
    {
        $I = $this;
        $I->amOnPage('/s/contacts');
        $contactName=$I->grabTextFrom("//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]");
        $I->click("#leadTable > tbody > tr:nth-child($place) > td:nth-child(2) > a > div");
        $I->waitForText($contactName, 10, '#app-content > div > div.page-header > div > div.col-xs-5.col-sm-6.col-md-5.va-m > h1 > div > span:nth-child(1)');
        $I->see('Admin User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]');
    }
}
