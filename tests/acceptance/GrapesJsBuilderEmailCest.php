<?php

declare(strict_types=1);

use Page\Acceptance\EmailsPage;
use Page\Acceptance\SegmentsPage;

final class GrapesJsBuilderEmailCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function conflictingSegmentsBlockBuilderLaunch(AcceptanceTester $I): void
    {
        $segmentName = 'Issue 17552 '.date('YmdHis');
        $segmentAlias = 'issue-17552-'.date('YmdHis');

        $I->amOnPage('/s/segments/new');
        $I->waitForElementVisible('form[name="leadlist"]');
        $I->fillField('leadlist[name]', $segmentName);
        $I->fillField('leadlist[alias]', $segmentAlias);
        $I->click(SegmentsPage::$SAVE_AND_CLOSE_BUTTON);
        $I->waitForElementNotVisible('form[name="leadlist"]', AcceptanceTester::TIMEOUT);

        $I->amOnPage('/s/emails/new');
        $I->waitForElementVisible(EmailsPage::SELECT_SEGMENT_EMAIL, AcceptanceTester::TIMEOUT);
        $I->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->waitForElementVisible('form[name="emailform"]', AcceptanceTester::TIMEOUT);

        $I->fillField('emailform[subject]', 'Issue 17552');
        $I->fillField('emailform[name]', 'Issue 17552');

        $segmentFound = $I->executeJS(
            <<<'JS'
const segmentName = arguments[0];
const included = document.querySelector('#emailform_lists');
const excluded = document.querySelector('#emailform_excludedLists');

const option = Array.from(included.options).find(
    (item) => item.text.trim().startsWith(`${segmentName} (`)
);

if (!option) {
    return false;
}

mQuery(included)
    .val([option.value])
    .trigger('change')
    .trigger('chosen:updated');

mQuery(excluded)
    .val([option.value])
    .trigger('change')
    .trigger('chosen:updated');

return true;
JS,
            [$segmentName]
        );

        $I->assertTrue((bool) $segmentFound);

        $I->executeJS("document.querySelector('#emailform_buttons_builder').click();");

        $I->waitForElementVisible('.email-builder-segment-conflict', AcceptanceTester::TIMEOUT);
        $I->see(
            'A segment cannot be both included and excluded. Fix the segment selections before opening the builder.',
            '.email-builder-segment-conflict'
        );
        $I->dontSeeElement('.builder.builder-active');

        $I->executeJS(
            <<<'JS'
const excluded = document.querySelector('#emailform_excludedLists');

mQuery(excluded)
    .val([])
    .trigger('change')
    .trigger('chosen:updated');
JS
        );

        $I->executeJS("document.querySelector('#emailform_buttons_builder').click();");

        $I->waitForElementVisible('.builder.builder-active', AcceptanceTester::TIMEOUT);
        $I->dontSeeElement('.email-builder-segment-conflict');
    }
}
