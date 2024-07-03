<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\ContactPage;

class Contact extends \AcceptanceTester
{
    /**
     * Fill out the quick add form for creating a contact.
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
    }

    public function selectContactFromList()
    {
        $I           = $this;
        $contactName = $I->grabTextFrom('#leadTable tbody tr:first-child td:nth-child(2) a div');
        $I->see($contactName);
        $I->click(['link' => $contactName]);
        $I->waitForText($contactName, 30);
        $I->see($contactName);
    }
}
