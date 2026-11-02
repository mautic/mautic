<?php

declare(strict_types=1);

namespace Acceptance;

final class CompanyEnrichResearchCest
{
    public function researchPageShowsOperationsAndRequiresConfiguration(\AcceptanceTester $I): void
    {
        $I->login();
        $I->amOnPage('/s/companyenrich');
        $I->waitForElementVisible('#research_operation_chosen', \AcceptanceTester::TIMEOUT);
        $I->see('Research companies and professional profiles');
        $I->click('#research_operation_chosen');
        $I->see('Company workforce', '#research_operation_chosen');
        $I->see('Search professional profiles', '#research_operation_chosen');
        $I->click('#research_operation_chosen .chosen-single');
        $I->makeScreenshot('CompanyEnrichResearch');
        $I->fillField('#research_query', '{"domain":"example.com"}');
        $I->click('Run research');
        $I->waitForText('Install and enable CompanyEnrich', \AcceptanceTester::TIMEOUT);
        $I->dontSee('Research results');
    }
}
