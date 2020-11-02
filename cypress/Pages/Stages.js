"use strict";
class Stages {
    get addNewButton() {
        return  cy.get('i[class="fa fa-plus"]');
    }

    get stageName() {
        return  cy.get('#stage_name');
    }

    get stageWeight() {
        return   cy.get('#stage_weight');
    }

    get saveAndCloseButton() {
        return   cy.get('#stage_buttons_save_toolbar');
    }
    
    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }
    
    get searchAndClickOnFirstElement() {
        return   cy.get('#stageTable>tbody>tr>td>div>a');
    }

    get searchAndClickOnFirstCheckbox() {
        return   cy.get('#stageTable>tbody>tr>td>div>span>input');
    }

    get waitforPageLoad(){
        cy.get('div[class="content-body"]>div>div>div>h3').should('contain', 'Stage');
    }

    waitforStageCreation(){
        cy.get('#stageTable>tbody>tr>td>div>a').should('be.visible');
    }
  
}
const stages = new Stages();
module.exports = stages;
