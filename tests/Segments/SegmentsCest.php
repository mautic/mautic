<?php


class SegmentsCest
{
    private $isInitialized      = false;
    private $allLeads;

    public function _before(SegmentsTester $I)
    {
        if (!$this->isInitialized) {
            $this->isInitialized=true;
            $this->allLeads     = $I->FillInitialData();
        }
        $I->loginToMautic();
    }

    public function _after(SegmentsTester $I)
    {
    }

    // tests
    public function TestEqualFilter(SegmentsTester $I)
    {
        $I->amOnPage('/s/contacts');
        $I->amOnPage('/s/segments/view/1');
        $I->canSee($this->allLeads[0]['firstname']);
        $I->canSee($this->allLeads[0]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id'=> '1']);
        $I->amOnPage('/s/segments/view/2');
        $I->canSee($this->allLeads[0]['firstname']);
        $I->canSee($this->allLeads[0]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id'=> '2']);
    }

    public function TestNotEqualFilter(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/3');
        $I->cantSee($this->allLeads[0]['firstname']);
        $I->cantSee($this->allLeads[0]['lastname']);
        $I->canSeeNumRecords(count($this->allLeads) - 1, 'mautic_lead_lists_leads', ['leadlist_id'=> '3']);
    }

    public function TestEmpty(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/4');
        $I->canSee($this->allLeads[3]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id'=> '4']);
    }

    public function TestNotEmpty(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/5');
        $I->canSee($this->allLeads[0]['firstname']);
        $I->canSee($this->allLeads[0]['lastname']);
        $I->canSee($this->allLeads[1]['firstname']);
        $I->canSee($this->allLeads[1]['lastname']);
        $I->canSee($this->allLeads[3]['lastname']);
        $I->canSee($this->allLeads[4]['firstname']);
        $I->canSee($this->allLeads[4]['lastname']);
        $I->canSee($this->allLeads[5]['firstname']);
        $I->canSee($this->allLeads[5]['lastname']);
        $I->canSee($this->allLeads[7]['firstname']);
        $I->canSee($this->allLeads[7]['lastname']);
        $I->canSee($this->allLeads[8]['firstname']);
        $I->canSee($this->allLeads[8]['lastname']);
        $I->canSee($this->allLeads[9]['firstname']);
        $I->canSee($this->allLeads[9]['lastname']);
        $I->canSee($this->allLeads[10]['firstname']);
        $I->canSee($this->allLeads[10]['lastname']);
        $I->canSeeNumRecords(9, 'mautic_lead_lists_leads', ['leadlist_id'=> '5']);
    }

    public function TestLike(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/6');
        $I->canSee($this->allLeads[1]['firstname']);
        $I->canSee($this->allLeads[1]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id' => '6']);
    }

    public function TestNotLike(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/7');
        $I->cantSee($this->allLeads[2]['firstname']);
        $I->cantSee($this->allLeads[2]['lastname']);
        $I->canSeeNumRecords(count($this->allLeads) - 1, 'mautic_lead_lists_leads', ['leadlist_id' => '7']);
    }

    public function TestStartsWith(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/8');
        $I->canSee($this->allLeads[0]['firstname']);
        $I->canSee($this->allLeads[0]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id' => '8']);
    }

    public function TestEndsWith(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/9');
        $I->canSee($this->allLeads[2]['firstname']);
        $I->canSee($this->allLeads[2]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id' => '9']);
    }

    public function TestContains(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/10');
        $I->canSee($this->allLeads[0]['firstname']);
        $I->canSee($this->allLeads[0]['lastname']);
        $I->canSee($this->allLeads[1]['firstname']);
        $I->canSee($this->allLeads[1]['lastname']);
        $I->canSeeNumRecords(2, 'mautic_lead_lists_leads', ['leadlist_id' => '10']);
    }

    public function TestIncluding(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/11');
        $I->canSee($this->allLeads[18]['firstname']);
        $I->canSee($this->allLeads[18]['lastname']);
        $I->canSee($this->allLeads[19]['firstname']);
        $I->canSee($this->allLeads[19]['lastname']);
        $I->canSeeNumRecords(2, 'mautic_lead_lists_leads', ['leadlist_id' => '11']);
    }

    public function TestExcluding(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/12');
        $I->cantSee($this->allLeads[18]['firstname']);
        $I->canSeeNumRecords(count($this->allLeads) - 1, 'mautic_lead_lists_leads', ['leadlist_id' => '12']);
    }

    public function TestGreaterThan(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/13');
        $I->canSee($this->allLeads[20]['firstname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id' => '13']);
    }

    public function TestGreaterThanOrEqual(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/14');
        $I->canSee($this->allLeads[20]['firstname']);
        $I->canSee($this->allLeads[21]['firstname']);
        $I->canSee($this->allLeads[24]['firstname']);
        $I->canSeeNumRecords(3, 'mautic_lead_lists_leads', ['leadlist_id' => '14']);
    }

    public function TestLessThan(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/15');
        $I->cantSee($this->allLeads[20]['firstname']);
        $I->cantSee($this->allLeads[21]['firstname']);
        $I->cantSee($this->allLeads[24]['firstname']);
        $I->cantSee($this->allLeads[25]['firstname']);
        $I->canSeeNumRecords(count($this->allLeads) - 4, 'mautic_lead_lists_leads', ['leadlist_id' => '15']);
    }

    public function TestLessOrEqual(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/16');
        $I->cantSee($this->allLeads[20]['firstname']);
        $I->cantSee($this->allLeads[21]['firstname']);
        $I->cantSee($this->allLeads[22]['firstname']);
        $I->cantSee($this->allLeads[24]['firstname']);
        $I->cantSee($this->allLeads[25]['firstname']);
        $I->canSeeNumRecords(count($this->allLeads) - 5, 'mautic_lead_lists_leads', ['leadlist_id' => '16']);
    }

    public function Test(SegmentsTester $I)
    {
        $I->amOnPage('/s/segments/view/6');
        $I->canSee($this->allLeads[1]['firstname']);
        $I->canSee($this->allLeads[1]['lastname']);
        $I->canSeeNumRecords(1, 'mautic_lead_lists_leads', ['leadlist_id' => '6']);
    }
}
