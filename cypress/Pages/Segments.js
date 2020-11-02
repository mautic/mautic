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
        return  cy.get('.bg-auto > [data-toggle="tooltip"] > a');
    }

    get filterDropDown(){
        return cy.get('.chosen-single > span')
    }

    get filterSearchBox(){
        return cy.get('.chosen-search-input');
    }

    get filterField(){
        return     cy.get('.active-result')
    }

    get editSegment(){
        return     cy.get('a[href*="/s/segments/edit"]');
    }

    get filterValue(){
        return   cy.get('#leadlist_filters_1_filter')
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

    waitforSegmentCreation(){
        cy.get('#leadListTable>tbody>tr>td>div>a').should('be.visible');
    }

    waitforSegmentUpdate(){
        cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }
}
const segment = new Segments();
module.exports = segment;
