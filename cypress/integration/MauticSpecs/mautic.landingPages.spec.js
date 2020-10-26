/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const landingPages = require("../../Pages/LandingPages");
const search=require("../../Pages/Search");

context("Create Landing Page", () => {
  it("Create a New Landing Page with embedded form", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.landingPagesSubSection.click({force: true});
    cy.wait(2000);
    landingPages.waitforPageLoad();
    landingPages.addNewButton.click();
    cy.wait(1000);
    landingPages.pageTitle.type('Test Landing Page');
    cy.wait(1000);
    landingPages.applyButton.click();
    cy.wait(4000);
    landingPages.saveAndCloseButton.click();
  });

  it("Create a New Landing Page for Deletion", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.landingPagesSubSection.click({force: true});
    cy.wait(2000);
    landingPages.waitforPageLoad();
    landingPages.addNewButton.click();
    cy.wait(1000);
    landingPages.pageTitle.type('Delete');
    cy.wait(1000);
    landingPages.applyButton.click();
    cy.wait(4000);
    landingPages.saveAndCloseButton.click();
  });
  
  it("Search and Delete Landing Page", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.landingPagesSubSection.click();
    cy.wait(1000);
    search.searchBox.clear();
    search.searchBox.type("Delete");
    cy.wait(1000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    cy.wait(1000);
    search.confirmDeleteButton.click();
    cy.wait(1000);
  });
});
