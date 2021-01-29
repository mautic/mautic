"use strict";
class CustomFields {
     waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Custom Fields');
    }
    get addNewButton() {
        return  cy.get('.std-toolbar > .btn');
    }

    get fieldLabel() {
        return  cy.get('#leadfield_label');
    }

    get ObjectSelectionDropDown() {
        return   cy.get('#leadfield_object_chosen > .chosen-single > span');
    }

    get ObjectSelector(){
        return cy.get('#leadfield_object');
    }

    get DataTypeSelector() {
        return   cy.get('#leadfield_type');
    }

    get DataTypeSelectionDropDown() {
        return   cy.get('#leadfield_type_chosen > .chosen-single > span');
    }

    get SaveAndCloseButton(){
        return cy.get('#leadfield_buttons_save_toolbar');
    }

    get selectAllCustomField(){
        return cy.get('#customcheckbox-one0');
    }

    get clickOnDropdownToDelete(){
        return cy.get('#leadFieldTable>thead>tr>th>div>div>button>i');
    }

    get deleteSelectedCustomField(){
        return cy.get('#leadFieldTable>thead>tr>th>div>div>ul');
    }

    get confirmationWindowForDelete(){
        return cy.get('button[class="btn btn-danger"]');
    }

    waitTillConfirmationWindowGetsLoaded(){
        cy.get('button[class="btn btn-danger"]').should('be.visible');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

}
const customFields = new CustomFields();
module.exports = customFields;
