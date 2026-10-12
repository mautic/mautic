<?php

declare(strict_types=1);

use Page\Acceptance\EmailsPage;
use Page\Acceptance\SegmentsPage;
use Step\Acceptance\EmailStep;

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

        $this->createSegment($I, $segmentName, $segmentAlias);

        $this->openSegmentEmailForm($I);

        $I->fillField('emailform[subject]', 'Issue 17552');
        $I->fillField('emailform[name]', 'Issue 17552');

        $this->selectConflictingSegments($I, $segmentName);

        $this->assertSegmentConflictBlocksBuilderLaunch($I);

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

    public function translationParentAllowsBuilderLaunchWithConflictingSegments(AcceptanceTester $I, EmailStep $email): void
    {
        $segmentName = 'Issue 17604 '.date('YmdHis');
        $segmentAlias = 'issue-17604-'.date('YmdHis');
        $parentName = 'Issue 17604 parent '.date('YmdHis');

        $this->createSegment($I, $segmentName, $segmentAlias);

        $email->createSegmentEmail($parentName);

        $this->openSegmentEmailForm($I);

        $I->fillField('emailform[subject]', 'Issue 17604 translation');
        $I->fillField('emailform[name]', 'Issue 17604 translation');

        $this->selectConflictingSegments($I, $segmentName);

        $this->assertSegmentConflictBlocksBuilderLaunch($I, true);

        $parentFound = $I->executeJS(
            <<<'JS'
const parentName = arguments[0];
const parent = document.querySelector('#emailform_segmentTranslationParent');
const option = Array.from(parent.options).find(
    (item) => item.text.trim().startsWith(`${parentName} (`)
);

if (!option) {
    return false;
}

mQuery(parent)
    .val(option.value)
    .trigger('change')
    .trigger('chosen:updated');

return true;
JS,
            [$parentName]
        );

        $I->assertTrue((bool) $parentFound);

        $I->executeJS("document.querySelector('#emailform_buttons_builder').click();");

        $I->waitForElementVisible('.builder.builder-active', AcceptanceTester::TIMEOUT);
        $I->dontSeeElementInDOM('.email-builder-segment-conflict');
        $I->dontSeeElementInDOM('.form-group.has-error #emailform_excludedLists');
    }

    private function createSegment(AcceptanceTester $I, string $segmentName, string $segmentAlias): void
    {
        $I->amOnPage('/s/segments/new');
        $I->waitForElementVisible('form[name="leadlist"]');
        $I->fillField('leadlist[name]', $segmentName);
        $I->fillField('leadlist[alias]', $segmentAlias);
        $I->click(SegmentsPage::$SAVE_AND_CLOSE_BUTTON);
        $I->waitForElementNotVisible('form[name="leadlist"]', AcceptanceTester::TIMEOUT);
    }

    private function openSegmentEmailForm(AcceptanceTester $I): void
    {
        $I->amOnPage('/s/emails/new');
        $I->waitForElementVisible(EmailsPage::SELECT_SEGMENT_EMAIL, AcceptanceTester::TIMEOUT);
        $I->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->waitForElementVisible('form[name="emailform"]', AcceptanceTester::TIMEOUT);
    }

    private function selectConflictingSegments(AcceptanceTester $I, string $segmentName): void
    {
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
    }

    private function assertSegmentConflictBlocksBuilderLaunch(AcceptanceTester $I, bool $assertExcludedListError = false): void
    {
        $I->executeJS("document.querySelector('#emailform_buttons_builder').click();");

        $I->waitForElementVisible('.email-builder-segment-conflict', AcceptanceTester::TIMEOUT);
        $I->see(
            'A segment cannot be both included and excluded. Fix the segment selections before opening the builder.',
            '.email-builder-segment-conflict'
        );
        if ($assertExcludedListError) {
            $I->seeElementInDOM('.form-group.has-error #emailform_excludedLists');
        }
        $I->dontSeeElement('.builder.builder-active');
    }
}
