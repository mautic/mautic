"use strict";
class Points {

    waitforActionPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Points');
    }
    waitforPointTriggerPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Point Triggers');
    }
  
    get manageActionSection() {
        return  cy.get('#mautic_point_index > span');
    }
    
    get addNewActionButton() {
        return   cy.get('#toolbar > div.std-toolbar.btn-group > a > span > span');
    }

    get actionName() {
        return   cy.get('#point_name');
    }

    get pointsToBeChanged() {
        return cy.get('#point_delta');
    }

    get actionDropDown() {
        return cy.get('#point_type_chosen > .chosen-single > span');
    }

    get opensAnEmailOption(){
        return cy.get('#point_type_chosen > div > ul > li').eq(4);
    }
 
    get saveAndCloseButton() {
        return   cy.get('#point_buttons_save_toolbar');
    }
    
    get manageTriggerSection() {
        return  cy.get('#mautic_pointtrigger_index > span');
    }

    
    get addNewPointsTriggerButton() {
        return  cy.get('div[class="std-toolbar btn-group"]>a');
    }
  
    get triggerName(){
        return cy.get('#pointtrigger_name');
    }

    get triggerPoints() {
        return cy.get('#pointtrigger_points');
    }

    get eventsTab(){
        return cy.get('#app-content > div.content-body > form > div.box-layout > div.col-md-9.bg-white.height-auto > div > div > ul > li:nth-child(2) > a')
    }

    get addEventButton() {
        return cy.get('#triggerEvents > div.mb-md > div > button');
    }

    get sendEmailEvent(){
        return cy.get('#triggerEvents > div.mb-md > div > ul > li[id="event_email.send"]');

    }

    get eventName() {
        return cy.get('#pointtriggerevent_name')
    }

    get emailSelector() {
        return cy.get('#pointtriggerevent_properties_email_chosen > a')
    }

    get firstEmail() {
        return cy.get('#pointtriggerevent_properties_email_chosen > div > ul > li');
    }

    get publishTrigger() {
        return cy.get('label[class="btn btn-default  btn-yes"]').eq(1);
    }

    get addButton() {
        return cy.get('.modal-form-buttons > .btn-save');
    }

    get saveAndCloseTriggerButton() {
        return cy.get('#pointtrigger_buttons_save_toolbar');
    }

    get searchAndGetFirstResult() {
        return cy.get('#pointTable>tbody>tr>td>div>a');
    }

    get searchAndGetFirstResultTriggerTable() {
        return cy.get('#triggerTable>tbody>tr>td>div>a');
    }

    get searchAndSelectFirstCheckBox() {
        return cy.get('#pointTable>tbody>tr>td>div>span>input');
    }

    get searchAndSelectFirstCheckBoxForTrigger() {
        return cy.get('#triggerTable>tbody>tr>td>div>span>input');
    }

    get editOptionsForFirstSelection() {
        return cy.get('#pointTable>tbody>tr>td>div>div>button');
    }

    get editOptionsForFirstSelectionForTrigger() {
        return cy.get('#triggerTable >tbody>tr>td>div>div>button');
    }

    get deleteOption() {
        return cy.get('a[href*="/delete"]');
    }

    get confirmWindowDelete() {
        return cy.get('div[class="modal-body text-center"]>button[class*="danger"]');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

    waitforActionToBeCreated(){
        cy.get('#pointTable>tbody>tr>td>div>a').should('be.visible');
    }

    waitforTriggerToBeCreated(){
        cy.get('#triggerTable>tbody>tr>td>div>a').should('be.visible');
    }
}

const points = new Points();
module.exports = points;
