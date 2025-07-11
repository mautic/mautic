<?php

namespace Acceptance;

use Page\Acceptance\ThemesPage;

class ThemeManagementCest
{
    public function _before(\AcceptanceTester $I)
    {
        $I->login('admin', 'Maut1cR0cks!');

        $I->click(ThemesPage::$dropDown); // gear icon
        $I->waitForElementVisible(ThemesPage::$dropDown_Themes, 30);

        $I->click(ThemesPage::$dropDown_Themes);
        $I->waitForText('Themes', 30); // let the page render
    }

    public function themesHaveNoBlankActions(\AcceptanceTester $I): void
    {
        $I->amOnPage(ThemesPage::$URL);
        $I->waitForElementVisible(ThemesPage::$themeTable, 30);

        $rows = $I->grabMultiple(ThemesPage::$themeRows);
        for ($i = 1; $i <= count($rows); ++$i) {
            // Grab all AJAX links in this row
            $ajaxXpathBase = '(//table[@id="themeTable"]//tr)['.$i.']//a[@data-toggle="ajax"]';
            $ajaxLinks     = $I->grabMultiple($ajaxXpathBase);

            $ajaxCount = count($ajaxLinks);
            for ($j = 1; $j <= $ajaxCount; ++$j) {
                $linkXpath = $ajaxXpathBase.'['.$j.']';

                // Fail only if there's no href attribute
                if (!$I->seeElement($linkXpath.'[@href]')) {
                    $I->fail("AJAX link in row $i (link $j) is missing an href.");
                }
            }
        }
    }
}
