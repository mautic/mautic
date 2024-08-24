<?php

use Page\Acceptance\CategoriesPage;
use Page\Acceptance\EmailsPage;
use Page\Acceptance\SegmentsPage;

/**
 * Inherited Methods.
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public function login($name, $password): void
    {
        $I = $this;
        // if snapshot exists - skipping login
        if ($I->loadSessionSnapshot('login')) {
            return;
        }
        // logging in
        $I->amOnPage('/s/login');
        $I->fillField('#username', $name);
        $I->fillField('#password', $password);
        $I->click('button[type=submit]');
        $I->waitForElement('h1.page-header-title', 30);
        // saving snapshot
        $I->saveSessionSnapshot('login');
    }

    public function createAContactSegment(string $name): void
    {
        $this->amOnPage(SegmentsPage::URL);
        $this->click(SegmentsPage::NEW_BUTTON);
        $this->wait(1);
        $this->fillField(SegmentsPage::SEGMENT_NAME, $name);
        $this->click(SegmentsPage::SAVE_AND_CLOSE_BUTTON);
    }

    public function createACategory(string $name): void
    {
        $this->amOnPage(CategoriesPage::URL);
        $this->wait(1);
        $this->click(CategoriesPage::NEW_BUTTON);
        $this->waitForElementClickable(CategoriesPage::BUNDLE_DROPDOWN);
        $this->click(CategoriesPage::BUNDLE_DROPDOWN);

        $this->waitForElementClickable(CategoriesPage::BUNDLE_EMAIL_OPTION);
        $this->click(CategoriesPage::BUNDLE_EMAIL_OPTION);
        $this->fillField(CategoriesPage::TITLE_FIELD, $name);
        $this->click(CategoriesPage::SAVE_AND_CLOSE);
    }

    public function createAnEmail(string $name): void
    {
        $this->amOnPage(EmailsPage::URL);
        $this->wait(1);
        $this->click(EmailsPage::NEW);
        $this->waitForElementClickable(EmailsPage::SELECT_SEGMENT_EMAIL);
        $this->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $this->fillField(EmailsPage::SUBJECT_FIELD, $name);
        $this->click(''.EmailsPage::CONTACT_SEGMENT_DROPDOWN);
        $this->waitForElementClickable(EmailsPage::CONTACT_SEGMENT_OPTION);
        $this->click(EmailsPage::CONTACT_SEGMENT_OPTION);
        $this->click(EmailsPage::SAVE_AND_CLOSE);
    }
}
