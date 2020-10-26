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
    
    get searchAndClickOnFirstElement() {
        return   cy.get('#stageTable>tbody>tr>td>div>a');
    }

    get searchAndClickOnFirstCheckbox() {
        return   cy.get('#stageTable>tbody>tr>td>div>span>input');
    }

    get waitforPageLoad(){
        cy.get('div[class="content-body"]>div>div>div>h3').should('contain', 'Stage');
    }
  
}
const stages = new Stages();
module.exports = stages;
