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
        return cy.get('#dwcTable>tbody>tr>td:nth-child(2)>a');
        return cy.wait(2000);
    }

    waitTillSearchedElementGetsVisible(){
        return cy.get('#dwcTable>tbody>tr>td>a');
    }

    get typeContent(){
        return cy.get('.fr-element'); // Community Specific
    }

    get editDynamicContent(){
        return cy.get('#toolbar > div.std-toolbar.btn-group > a:nth-child(1)'); // Community Specific
    }

}
const dynamicContent = new DynamicContent();
module.exports = dynamicContent;

