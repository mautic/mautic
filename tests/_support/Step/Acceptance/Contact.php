<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\ContactPage;

class Contact extends \AcceptanceTester
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
     * Click on the dropdown menu for a contact.
     *
     * @param int $place the position of the contact in the list (starting from 1)
     */
    public function dropDownMenu($place)
    {
        $I = $this;
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/button");
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
}
