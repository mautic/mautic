"use strict";
class Segments {

    waitForPageLoad(){
        cy.get('h3.pull-left').should('contain', 'Contact Segments');
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

    get checkContactsUnderSegment(){
        return cy.get('a[href*="?search=segment"]');
    }

    get checkDetailContactsUnderSgemnt(){
        return cy.get('#leadTable>tbody>tr>td>a>div');
    }

    get filterOperator(){
        return cy.get('#leadlist_filters_1_operator');
    }

    get secondFilterOperator(){
        return cy.get('#leadlist_filters_2_operator');
    }

    get thirdFilterOperator(){
        return cy.get('#leadlist_filters_3_operator');
    }

    get secondFilterProperties(){
        return cy.get('#leadlist_filters_2_properties_filter');
    }

    get thirdFilterProperties(){
        return cy.get('#leadlist_filters_3_properties_filter');
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
}
const segment = new Segments();
module.exports = segment;
