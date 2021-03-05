"use strict";
class Contacts {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Contacts');
    }
    get addNewButton() {
        return cy.get('[href="/s/contacts/new"]');
    }

    get companySelector(){
        return cy.get('#lead_companies_chosen > div > ul');
    }

    get companySearch(){
        return cy.get('#lead_companies_chosen > ul > li.search-field');
    }

    get searchAndClickForFirstElement() {
        return cy.get('#leadTable>tbody>tr>td>a>div');
    }

    get quickAddButton() {
        return cy.get('.quickadd');
    }

    get getContactPoints() {
        return cy.get('#leadTable>tbody>tr>td>span[class="label label-default"]');
    }

    waitForContactOpen(){
        return  cy.get('div[class="std-toolbar btn-group"]>a[href*="edit"]').should('be.visible');
    }

    get alertMessage () {
        return cy.get('.alert-growl');
    }

    get editContact(){
        return cy.get('div[class="std-toolbar btn-group"]>a[href*="edit"]');
    }
    get title() {
        return cy.get("#lead_title");
    }
    get firstName() {
        return   cy.get("#lead_firstname");
    }
    get lastName() {
        return cy.get("#lead_lastname");
    }
    get leadEmail() {
        return cy.get("#lead_email");
    }
    get leadCity() {
        return cy.get("#lead_city");
    }

    waitForContactEditPageOpen(){
        return  cy.get('#lead_city').should('be.visible');
    }

    get SaveButton() {
        return   cy.get("#lead_buttons_save_toolbar");
    }
    get logout() {
        return $('[data-cy="logoutMenu"]');
    }
    get closeButton() {
       return  cy.get('[href="/s/contacts"] > :nth-child(1) > .hidden-xs');
    }

    get importExportDropdownMenu() {
        return  cy.get('.std-toolbar > .dropdown-toggle > .fa');
    }

    get importButton(){
        return  cy.get('a[href*="import/new"]');
    }

    get clickOnCustomObject(){
        return  cy.get('a[href*="custom-object"]');
    }

    get clickOnLinkExisting(){
        return  cy.get('a[href*="filterEntity"]');
    }

    get clickOnDropdwonForLinkObject(){
        return  cy.get('table[id*="custom-items-"]>tbody>tr>td>div>div>button');
    }

    get clickOnLinkObject(){
        return  cy.get('table[id*="custom-items-"]>tbody>tr>td>div>div>ul>li');
    }

    get checkNoResultFoundMessage() {
        return cy.get('div[id*="custom-item-"]>div>h4');
    }

    get closeThePopUpWindow() {
        return cy.get('#customItemLookupModal>div>div>div>button');
    }

    get customObjectTable() {
        return cy.get('table[id*="custom-items-"]>tbody>tr>td>div>a');
    }

    get updateContactPoints() {
        return cy.get('#lead_points');
    }

    get numericFieldOne() {
        return cy.get('#lead_numericfield_1');
    }

    get numericFieldSecond() {
        return cy.get('#lead_numericfield_2');
    }

    get dateFieldOne() {
        return cy.get('#lead_datefield_1');
    }

    get dateFieldSecond() {
        return cy.get('#lead_datefield_2');
    }

    get dateFieldThird() {
        return cy.get('#lead_datefield_3');
    }

    get booleanCustomField_Yes() {
        return cy.get('label[class="btn btn-default  btn-yes"]');
    }

    get booleanCustomField_No() {
        return cy.get('label[class="btn btn-default  btn-no"]');
    }

    get contactList() {
        return cy.get('#leadTable>tbody>tr>td>a');
    }

    get contactDetailsTab_DateField1Value() {
        return cy.get('#core>div>div>table>tbody>tr>td').eq(7)
    }

    get contactDetailsTab_DateField2Value() {
        return cy.get('#core>div>div>table>tbody>tr>td').eq(5)
    }

    get contactDetailsTab_DateField3Value() {
        return cy.get('#core>div>div>table>tbody>tr>td').eq(3)
    }

    get contactDetailsTab_LastDateActive() {
        return cy.get('#core>div>div>table>tbody>tr>td').eq(43)
    }

    get getContactDetails() {
        cy.get('div[class="hr-expand nm"]>span>a').should('be.visible');
        return cy.get('div[class="hr-expand nm"]>span>a');
    }

    waitTillLinkPopupOpen(){
        cy.get('#customItemLookupModal-label').should('be.visible');
    }

    waitForContactCreation(){
        return  cy.get('div[class="mt-sm points-panel text-center"]>h1').should('be.visible');
    }

    waitTillSearchResultGetsDisplayed(){
        cy.get('#leadTable>tbody>tr>td>a').should('not.be.empty');
    }

    get createdCustomFieldIsDisplayed(){
        return cy.get('div[id="core"]>div>div>div>div>div>div>label');
    }
}
const contact = new Contacts();
module.exports = contact;
