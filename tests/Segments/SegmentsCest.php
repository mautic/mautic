<?php


class SegmentsCest
{
    private $isInitialized      = false;

    public function _before(SegmentsTester $I)
    {
        if (!$this->isInitialized) {
            $this->isInitialized=true;
            $I->FillInitialData();
        }
        $I->loginToMautic();
    }

    public function _after(SegmentsTester $I)
    {
    }

    // tests
    public function tryToTest(SegmentsTester $I)
    {
        $I->amOnPage('/s/contacts');
        $I->runShellCommand('php app/console mautic:segments:update');
        $I->amOnPage('/s/segments/view/1');
        $I->canSee('John');
        $I->canSee('Sparrow');
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id'=> '1']);
    }
}
