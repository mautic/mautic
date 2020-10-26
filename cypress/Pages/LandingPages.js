"use strict";
class LandingPages {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Landing Pages');
    }
    get addNewButton() {
        return cy.get('#toolbar > div.std-toolbar.btn-group > a');
    }

    get pageTitle() {
        return  cy.get('#page_title');
    }

    get applyButton(){
        return cy.get('#page_buttons_apply_toolbar');
    }

    get saveAndCloseButton() {
        return cy.get("#page_buttons_save_toolbar");
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

  

}
const landingPages = new LandingPages();
module.exports = landingPages;
