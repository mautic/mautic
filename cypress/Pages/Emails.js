"use strict";
class Emails{

    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Emails');
    }

    get addNewButton() {
        return cy.get('.std-toolbar > .btn');
    }

    get emailInternalName() {
        return  cy.get('#emailform_name');
    }

    get emailSubject(){
        return cy.get('#emailform_subject');
    }

    get templateEmailSelector() {
        return  cy.get('button[class="btn btn-lg btn-default btn-nospin text-success"]');
    }

    get contactSegmentSelector() {
        return  cy.get('#emailform_lists_chosen');
    }

    get segmentEmailSelector() {
        return  cy.get('button[class="btn btn-lg btn-default btn-nospin text-primary"]');
    }

    get firstSegmentEmailSelector() {
        return  cy.get('#emailform_lists_chosen>div>ul>li').eq(0);
    }

    get saveEmailButton() {
        return cy.get('#emailform_buttons_save_toolbar');
    }

    get closeButton(){
        return cy.get('[href="/s/emails"] > :nth-child(1) > .hidden-xs');
    }

    get searchAndSelectEmail(){
        return cy.get('table[class="table table-hover table-striped table-bordered email-list"]>tbody>tr>td>div>a');
    }

    get scheduleSegmentEmail(){
        return cy.get('[data-header="Schedule testSegmentEmailCypress"] > :nth-child(1)');
    }

    get scheduleSegmentCalender(){
        return cy.get('.div[class="input-group"]>input').eq(3);
    }

    get scheduleButton(){
        return cy.get('.modal-form-buttons > .btn-save');
    }

    get emailEditButton(){
        return cy.get('a[href*="emails/edit"]');
    }

    get selectAuroraTheme(){
        return cy.get('#email-container>div>div>div>div>a[data-theme="aurora"]');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

}
const email = new Emails();
module.exports = email;
