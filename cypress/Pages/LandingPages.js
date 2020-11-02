"use strict";
class LandingPages {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Landing Pages');
    }

    waitforPageLoadForLandingPage(){
        cy.get('h3.pull-left');
    }

    waitforEditLandingPage(){
        cy.get('h3.pull-left').should('contain', 'Edit Page');
    }

    waitforNewPageLandingCreationLogo (){
        cy.get('h3.pull-left').should('contain', 'New Page');
    }

    get addNewButton() {
        return cy.get('#toolbar > div.std-toolbar.btn-group > a');
    }

    get pageTitle() {
        return  cy.get('#page_title');
    }

    get selectSkylineTheme() {
        return  cy.get('a[data-theme="skyline"]');
    }

    get applyButton(){
        return cy.get('#page_buttons_apply_toolbar');
    }

    get saveAndCloseButton() {
        return cy.get("#page_buttons_save_toolbar");
    }

    get searchAndSelectFIrstItem() {
        return cy.get("#pageTable>tbody>tr>td>a");
    }

    get builderButton() {
        return  cy.get('#page_buttons_builder_toolbar');
    }

    get dataSlotContainer(){
        return  cy.get("body > div > center > table > tbody > tr > td > div > div > div:nth-child(8)");
    }

    get applyButtonOnBuilderPage(){
        return cy.get("#app-content > div > div.builder.page-builder.builder-active > div.builder-panel > div.builder-panel-top > div:nth-child(1) > div > button.btn.btn-primary.btn-apply-builder");
    }

    get closeBuilderButtonOnBuilderPage() {
        return cy.get("#app-content > div > div.builder.page-builder.builder-active > div.builder-panel > div.builder-panel-top > div:nth-child(1) > div > button.btn.btn-primary.btn-close-builder");
    }

    get insertTagDropdown() {
        return cy.get("#token-2 > i");
    }

    get formInTheList() {
        return cy.get("#slot > div:nth-child(2) > div > div > div.fr-toolbar.fr-desktop.fr-top.fr-basic > div:nth-child(22) > div > div > ul > li:nth-child(5) > a");
    }
    
    get editLandingPage() {
        return cy.get('a[href*="pages/edit"]');
    }

    waitforLandingPageCreation(){
        cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }

}
const landingPages = new LandingPages();
module.exports = landingPages;
