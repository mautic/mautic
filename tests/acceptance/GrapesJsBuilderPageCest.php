<?php

declare(strict_types=1);

use Facebook\WebDriver\WebDriverKeys;

final class GrapesJsBuilderPageCest
{
    private const EDITED_CONTENT = 'GrapesJS E2E content';

    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function landingPageContentSurvivesBuilderSaveAndReload(AcceptanceTester $I): void
    {
        $I->amOnPage('/s/pages/new');
        $I->waitForElementVisible('form[name="page"]');
        $I->fillField('input[name="page[title]"]', 'GrapesJS E2E '.date('YmdHis'));

        $I->click('.btn-builder');
        $this->editCanvasText($I);

        $I->click('#btn-views-apply');
        $I->waitForJS("return window.location.pathname.includes('/s/pages/edit/')", 30);

        $I->seeInCurrentUrl('/s/pages/edit/');
        $html = (string) $I->executeJS("return document.querySelector('textarea.builder-html').value;");
        $editorState = (string) $I->executeJS("return document.querySelector('textarea.builder-json').value;");
        $I->assertStringContainsString(self::EDITED_CONTENT, $html);
        $I->assertJson($editorState);
        $I->assertStringContainsString(self::EDITED_CONTENT, $editorState);

        $I->click('.gjs-pn-btn[title="Close"]');
        $I->waitForElementNotVisible('.builder.builder-active');
        $I->reloadPage();
        $I->waitForElementVisible('.btn-builder');

        $I->click('.btn-builder');
        $this->waitForCanvasContent($I);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->see(self::EDITED_CONTENT);
        $I->switchToIFrame();

        $I->click('.gjs-pn-btn[title="Close"]');
        $I->waitForElementNotVisible('.builder.builder-active');
        $alias = (string) $I->executeJS("return document.querySelector('input[name=\"page[alias]\"]').value;");
        $I->amOnPage('/'.$alias);
        $I->see(self::EDITED_CONTENT);
    }

    private function editCanvasText(AcceptanceTester $I): void
    {
        $this->waitForCanvasContent($I);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->doubleClick('(//*[normalize-space(text()) and not(self::script) and not(self::style) and not(self::html) and not(self::head) and not(self::body)])[1]');
        $I->waitForElementVisible('[contenteditable="true"]', 30);
        $I->pressKey('[contenteditable="true"]', [WebDriverKeys::CONTROL, 'a']);
        $I->pressKey('[contenteditable="true"]', self::EDITED_CONTENT);
        $I->see(self::EDITED_CONTENT, '[contenteditable="true"]');
        $I->switchToIFrame();
    }

    private function waitForCanvasContent(AcceptanceTester $I): void
    {
        $I->waitForElementVisible('iframe.gjs-frame', 30);
        $I->switchToIFrame('iframe.gjs-frame');
        $I->waitForElementVisible('body', 30);
        $I->switchToIFrame();
    }
}
