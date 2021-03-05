/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const landingPages = require("../../Pages/LandingPages");
const search=require("../../Pages/Search");

var landingPageName = "TestLandingPage";

context("Verify that user is able to create and delete landing pages", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/pages");
  });

  it("Create a New Landing Page with embedded form", () => {
    landingPages.waitforPageLoad();
    landingPages.addNewButton.click();
    landingPages.waitforNewPageLandingCreationLogo()
    landingPages.pageTitle.type(landingPageName);
    landingPages.saveAndCloseButton.click();
    landingPages.waitforLandingPageCreation();
  });

  it("Edit newly added landing page", () => {
    landingPages.waitforPageLoad();
    cy.visit('/s/pages?search=' + landingPageName)
    landingPages.searchAndSelectFIrstItem.contains(landingPageName).click();
    landingPages.waitTillClickedPageGetsOpen();
    landingPages.editLandingPage.click();
    landingPages.waitforEditLandingPage;
    landingPages.selectSkylineTheme.click();
    landingPages.saveAndCloseButton.click();
    landingPages.waitforLandingPageCreation();
  });
  
  it("Search and delete newly added Landing Page", () => {
    landingPages.waitforPageLoad();
    cy.visit('/s/pages?search=' + landingPageName)
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
});
