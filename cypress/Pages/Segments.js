"use strict";
class Segments {

    waitForPageLoad(){
        cy.get('h3.pull-left').should('contain', 'Contact Segments');
    }

    waitforSegmentPageLoad(){
        cy.get('h3.pull-left').should('contain', 'segment'); //Community specific
    }

    waitTillSearchedElementGetsVisible(){
        return cy.get('#leadListTable>tbody>tr>td>div>a').should('be.visible');
    }

    waitTillClickedSegmentGetsOpen(){
        return cy.get('a[href*="/s/segments/edit"]').should('be.visible');
    }

    get addNewButton() {
        return cy.get('.std-toolbar > .btn');
    }

    get segmentName() {
        return  cy.get('#leadlist_name');
    }

    get filterTab() {
        return  cy.get('.bg-auto > [data-toggle="tooltip"] > a'); //Community specific
    }

    get filterDropDown(){
        return cy.get('#available_filters_chosen > .chosen-single') //Community specific
    }

    get filterSearchBox(){
        return cy.get('#available_filters_chosen > div > div > input'); //Community specific
    }

    get filterField(){
        return     cy.get('.active-result'); //Community specific
    }

    get filterCityField(){
        return     cy.get('#available_filters_chosen > div > ul > li.active-result.group-option.segment-filter.user'); // Community specific
    }

    get editSegment(){
        return     cy.get('a[href*="/s/segments/edit"]');
    }

    get filterValue(){
        return   cy.get('#leadlist_filters_1_filter'); //Community specific
    }

    get saveAndCloseButton(){
        return    cy.get('#leadlist_buttons_save_toolbar');
    }

    get SearchBox() {
        return cy.get('#list-search');
    }

    get searchAndSelectSegment() {
        return cy.get('table[class="table table-hover table-striped table-bordered"]>tbody>tr>td>div>a');
    }

    get firstCheckbox(){
        return cy.get(':nth-child(1) > :nth-child(1) > .input-group > .input-group-addon > .list-checkbox');
    }

    get firstDropDown() {
        return cy.get('tbody > :nth-child(1) > :nth-child(1) > .input-group > .input-group-btn > .btn');
    }

    get deleteOption() {
        return cy.get(':nth-child(1) > :nth-child(1) > .input-group > .input-group-btn > .pull-left > :nth-child(3) > a > :nth-child(1) > span');
    }

    get deleteConfirmation(){
        return cy.get('.btn-danger');
    }

    get leadListFilter(){
        return cy.get('#leadlist_filters_1_glue');
    }

    get secondFilterTextBox(){
        return cy.get('#leadlist_filters_1_properties_filter');
    }

    get getSegment(){
        return cy.get('tbody > :nth-child(1) > :nth-child(2) > div > a'); //Community specific
    }

    get getContactInSegment(){
        return cy.get(':nth-child(1) > .panel > .box-layout > .col-xs-8 > .panel-body > .fw-sb > a > span'); //Community specific
    }

    get checkDetailContactsUnderSegment(){
        return cy.get('#leadTable>tbody>tr>td>a>div');
    }

    get clickFilterOperator(){
        return cy.get('#leadlist_filters > div > div.panel-body > div.col-xs-6.col-sm-3.padding-none');
    }

    get filterOperator(){
        return cy.get('#leadlist_filters_1_operator');
    }

    get secondFilterOperator(){
        return cy.get('#leadlist_filters_2_operator');
    }
    get selectContains(){
        return cy.get('#leadlist_filters_2_operator > option:nth-child(11)');
    }

    get thirdFilterOperator(){
        return cy.get('#leadlist_filters_3_operator');
    }

    get secondFilterProperties(){
        return cy.get('#leadlist_filters_2_filter');
    }

    get thirdFilterProperties(){
        return cy.get('#leadlist_filters_3_filter');
    }

    get clickOnFourthFilterProperties(){
        return cy.get('#leadlist_filters_4_properties_filter_chosen>a');
    }

    get typeFourthFilterInput(){
        return cy.get('#leadlist_filters_4_properties_filter_chosen>div>div>input');
    }

    get selectFourthTypedInput(){
        return cy.get('#leadlist_filters_4_properties_filter_chosen>div>ul>li');
    }

    waitforSegmentCreation(){
        cy.get('#leadListTable>tbody>tr>td>div>a').should('be.visible');
    }

    waitforSegmentUpdate(){
        cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }

    waitTillNewSegmentGetsOpen(){
        cy.get('#leadlist_name').should('be.visible');
    }

    waitTillFilterOptionGetsLoaded(){
        cy.get('#leadlist_filters_1_filter').should('be.visible'); //Community specific
    }

    waitTillSecondOperatorFilterGetsLoaded(){
        cy.get('#leadlist_filters_2_operator').should('be.visible');
    }

    waitTillThirdOperatorFilterGetsLoaded(){
        cy.get('#leadlist_filters_3_operator').should('be.visible');
    }

    waitTillFourthOperatorFilterGetsLoaded(){
        cy.get('#leadlist_filters_4_operator').should('be.visible');
    }

    get checkContactsUnderSegment(){
        return cy.get('#leadListTable > tbody > tr > td:nth-child(3)');
    }
}
const segment = new Segments();
module.exports = segment;
