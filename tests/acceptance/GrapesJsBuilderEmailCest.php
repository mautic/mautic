<?php

declare(strict_types=1);

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\EmailsPage;
use Step\Acceptance\SegmentStep;

final class GrapesJsBuilderEmailCest
{
    private const EDITED_CONTENT = 'GrapesJS email recovery content';
    private const EMAIL_FORM = 'form[name="emailform"]';
    private const VALIDATION_MODAL_BODY = '.gjs-mdl-dialog .panel-body';
    private const ACTIVE_BUILDER = '.builder.builder-active';
    private const EDITABLE_CONTENT = '[contenteditable="true"]';

    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function emailContentSurvivesValidationErrorAndRecovery(AcceptanceTester $I, SegmentStep $segment): void
    {
        $segmentName = 'Issue 17552 '.date('YmdHis');
        $emailName   = 'Issue 17552 recovery '.date('YmdHis');

        $segment->createAContactSegment($segmentName);
        $I->waitForElementNotVisible('form[name="leadlist"]', AcceptanceTester::TIMEOUT);

        $I->amOnPage('/s/emails/new');
        $I->waitForElementVisible(EmailsPage::SELECT_SEGMENT_EMAIL, AcceptanceTester::TIMEOUT);
        $I->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->waitForElementVisible(self::EMAIL_FORM, AcceptanceTester::TIMEOUT);
        $I->fillField(EmailsPage::SUBJECT_FIELD, $emailName);
        $I->fillField('emailform[name]', $emailName);
        $this->selectConflictingSegments($I, $segmentName);

        $this->openBuilder($I);
        $this->editCanvasText($I);

        $I->click('#btn-views-apply');
        $I->waitForElementVisible(self::VALIDATION_MODAL_BODY, AcceptanceTester::TIMEOUT);
        $I->see('excludedLists: The same segment cannot be excluded and included in the same time.', self::VALIDATION_MODAL_BODY);
        $I->see(
            'Your builder content is preserved in this form, but has not been saved. Close the builder, correct the form errors, and save again.',
            self::VALIDATION_MODAL_BODY
        );

        $I->click('.gjs-mdl-dialog .panel-footer button');
        $I->waitForElementNotVisible('.gjs-mdl-dialog', AcceptanceTester::TIMEOUT);
        $I->click('.gjs-pn-btn[title="Close"]');
        $I->waitForElementNotVisible(self::ACTIVE_BUILDER, AcceptanceTester::TIMEOUT);
        $html = (string) $I->executeJS("return document.querySelector('textarea.builder-html').value;");
        $I->assertStringContainsString(self::EDITED_CONTENT, $html);

        $I->executeJS(
            <<<'JS'
mQuery('#emailform_excludedLists')
    .val([])
    .trigger('change')
    .trigger('chosen:updated');
JS
        );
        $I->click(EmailsPage::SAVE_AND_CLOSE);
        $I->waitForText($emailName, AcceptanceTester::TIMEOUT, 'h1.page-header-title');
        $I->waitForElementClickable(EmailsPage::EDIT_BUTTON, AcceptanceTester::TIMEOUT);
        $I->click(EmailsPage::EDIT_BUTTON);
        $I->waitForElementVisible(self::EMAIL_FORM, AcceptanceTester::TIMEOUT);
        $I->reloadPage();
        $I->waitForElementVisible(self::EMAIL_FORM, AcceptanceTester::TIMEOUT);
        $html = (string) $I->executeJS("return document.querySelector('textarea.builder-html').value;");
        $I->assertStringContainsString(self::EDITED_CONTENT, $html);

        $this->openBuilder($I);
        $this->waitForCanvasContent($I);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->see(self::EDITED_CONTENT);
        $I->switchToIFrame();
        $I->click('.gjs-pn-btn[title="Close"]');
        $I->waitForElementNotVisible(self::ACTIVE_BUILDER, AcceptanceTester::TIMEOUT);
    }

    private function editCanvasText(AcceptanceTester $I): void
    {
        $this->waitForCanvasContent($I);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->doubleClick('(//*[normalize-space(text()) and not(self::script) and not(self::style) and not(self::html) and not(self::head) and not(self::body)])[1]');
        $I->waitForElementVisible(self::EDITABLE_CONTENT, AcceptanceTester::TIMEOUT);
        $I->pressKey(self::EDITABLE_CONTENT, [WebDriverKeys::CONTROL, 'a']);
        $I->pressKey(self::EDITABLE_CONTENT, self::EDITED_CONTENT);
        $I->see(self::EDITED_CONTENT, self::EDITABLE_CONTENT);
        $I->switchToIFrame();
    }

    private function openBuilder(AcceptanceTester $I): void
    {
        $I->waitForJS("return typeof Mautic.launchBuilder === 'function' && Mautic.launchBuilder.toString().includes('builder-active') && document.querySelector('#emailform_buttons_builder') !== null;", AcceptanceTester::TIMEOUT);
        $I->waitForJS("return document.querySelector('#emailform_buttons_builder').disabled === false;", AcceptanceTester::TIMEOUT);
        $I->executeJS("document.querySelector('#emailform_buttons_builder').click();");
        $I->waitForElementVisible(self::ACTIVE_BUILDER, AcceptanceTester::TIMEOUT);
    }

    private function waitForCanvasContent(AcceptanceTester $I): void
    {
        $I->waitForElementVisible('iframe.gjs-frame', AcceptanceTester::TIMEOUT);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->waitForElementVisible('body', AcceptanceTester::TIMEOUT);
        $I->switchToIFrame();
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
}
