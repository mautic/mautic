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

}
const customFields = new CustomFields();
module.exports = customFields;
