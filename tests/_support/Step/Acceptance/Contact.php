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

    public function selectContactFromList($place)
    {
        $I           = $this;
        $xpath       = "//*[@id='leadTable']/tbody/tr[$place]/td[2]/a/div[1]";
        $contactName = $I->grabTextFrom($xpath);
        $I->see($contactName, $xpath);

        return $contactName;
    }

    public function dropDownMenu($place)
    {
        $I = $this;
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/button");
    }

    public function selectOptionFromDropDown($place, $option)
    {
        // 1-> Edit, 2-> Details, 3-> Send Email, 4-> Delete
        $I = $this;
        $I->waitForElementClickable("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a", 30);
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[$option]/a");
    }
}
