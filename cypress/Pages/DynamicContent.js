"use strict";
class DynamicContent {

    waitforPageLoad (){
        return cy.get('h3.pull-left').should('contain', 'Dynamic Content');
    }

    get createNewContent(){
        return cy.get('a[href*="dwc/new"]');
    }

    waitforCreationPageLoaded (){
        return cy.get('#dwc_name').should('be.visible');
    }

    waitTillDynamicContentCreationFlag(){
        return cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }

    get dynamicContentName(){
        return cy.get('#dwc_name');
    }

    get clickOnFirstSearchedElement(){
        return cy.get('#dwcTable>tbody>tr>td>a').should('be.visible');
    }

    waitTillSearchedElementGetsVisible(){
        return cy.get('#dwcTable>tbody>tr>td>a');
    }

    get editSelectedContent(){
        return cy.get('a[href*="dwc/edit"]');
    }

    get typeContent(){
        return cy.get('div[role="textbox"]');
    }

}
const dynamicContent = new DynamicContent();
module.exports = dynamicContent;

