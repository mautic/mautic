"use strict";
class Contacts {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Contacts');
    }
    get addNewButton() {
        return cy.get('[href="/s/contacts/new"]');
    }

    get searchAndClickForFirstElement() {
        return cy.get('#leadTable>tbody>tr>td>a>div');
    }

    get quickAddButton() {
        return cy.get('.quickadd');
    }

    get editContact(){
        return cy.get('i[class="fa fa-pencil-square-o"]');
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
        return  cy.get('.std-toolbar > .dropdown-menu > :nth-child(2) > a > :nth-child(1) > span');
    }
}
const contact = new Contacts();
module.exports = contact;
