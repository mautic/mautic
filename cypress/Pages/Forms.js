"use strict";
class Forms {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Forms');
    }
    get addNewButton() {
        return cy.get('#toolbar > div.std-toolbar.btn-group > a');
    }

    get formName() {
        return  cy.get('#mauticform_name');
    }

    get fieldsTab(){
        return cy.get('#fields-tab');
    }

    get standaloneFormSelector() {
        return  cy.get(':nth-child(2) > .panel > .panel-footer > .btn');
    }

    get fieldTypeDropDown(){
        return  cy.get(".available-fields > .chosen-container > .chosen-single > span");
    }

    get fieldTypeSearch(){
        return cy.get("#mauticforms_fields > div.row > div > div > div > div > input");
    }

    get firstResultOfFieldTypeSearch() {
        return cy.get("#mauticforms_fields > div.row > div > div > div > ul > li:nth-child(1)");
    }

    get fieldLabel() {
        return cy.get("#formfield_label")
    }

    get contactFieldTab() {
        return cy.get("form > :nth-child(1) > .nav > :nth-child(2) > a");
    }

    get contactFieldDropdown() {
        return cy.get('div[class="choice-wrapper"]>div>a[class="chosen-single chosen-default"]>span').eq(1);
    }

    get selectFromDropdown() {
        return cy.get('div[class="choice-wrapper"]>div>a[class="chosen-single chosen-default"]>span');
    }

    get contactFieldSearchBox() {
        return cy.get('div[class="chosen-container chosen-container-single chosen-with-drop chosen-container-active"]>div>div>input');
    }

    get contactFieldSearchFirstResult(){
        return cy.get('div[class="chosen-container chosen-container-single chosen-with-drop chosen-container-active"]>div>ul>li');
    }

    get addFieldButton(){
        return cy.get(".modal-form-buttons > .btn-save");
    }
    
    get saveFormButton() {
        return cy.get("#mauticform_buttons_save_toolbar");
    }

    get searchAndSelectFirstItem() {
        return cy.get('#formTable>tbody>tr>td>div>a');
    }

    get editForm() {
        return cy.get('a[href*="forms/edit"]');
    }

    waitforFormCreation(){
        cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }

}
const form = new Forms();
module.exports = form;
