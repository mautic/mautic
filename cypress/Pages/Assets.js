"use strict";
class Assets {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Assets');
    }
    get addNewButton() {
        return cy.get('.std-toolbar > .btn');
    }

    get remoteurl() {
        return cy.get('#asset_remotePath');
    }

    get remoteButton() {
        return cy.get('.col-md-7 > .row > .form-group > .choice-wrapper > .btn-group > :nth-child(2)');
    }

    get localButton(){
        return cy.get('.col-md-7 > .row > .form-group > .choice-wrapper > .btn-group > .active');
    }

    get assetTitle(){
        return cy.get('#asset_title');
    }

    get saveAndCloseButton() {
        return cy.get('#asset_buttons_save_toolbar');
    }

    get closeButton() {
        return cy.get('[href="/s/assets"] > :nth-child(1) > .hidden-xs');
    }

    get searchAndClickForFirstElement() {
        return cy.get('tbody > :nth-child(1) > :nth-child(2) > div > a');
    }

    get editAsset(){
    
        return cy.get('div[class="std-toolbar btn-group"]>a[href*="edit"]');
        
    }

    waitTillSearchResultGetsDisplayed(){
        cy.get('#assetTable>tbody>tr>td>a').should('not.be.empty');
    }
}
const asset = new Assets();
module.exports = asset;
