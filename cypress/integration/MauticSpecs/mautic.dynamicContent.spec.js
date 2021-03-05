/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const dynamicContent = require("../../Pages/DynamicContent");
const search=require("../../Pages/Search");

var dynamicContentText = "testDynamicContent";
context("Verify that user is able to create and edit dynamic content", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/dwc");
  });

  it("Create new dynamic content", () => {
    dynamicContent.waitforPageLoad();
    dynamicContent.createNewContent.click()
    dynamicContent.waitforCreationPageLoaded();
    dynamicContent.dynamicContentName.type(dynamicContentText);
    dynamicContent.typeContent.type("Test demo Content");
    search.saveAndCloseButton.click();
    dynamicContent.waitTillDynamicContentCreationFlag();
  });

  it("Edit newly added dynamic content", () => {
    dynamicContent.waitforPageLoad();
    cy.visit('/s/dwc?search='+ dynamicContentText);
    dynamicContent.clickOnFirstSearchedElement.contains(dynamicContentText).click();
    dynamicContent.waitTillDynamicContentCreationFlag();
    dynamicContent.editSelectedContent.click();
    dynamicContent.typeContent.clear();
    dynamicContent.typeContent.type("Test Demo Content");
    search.saveAndCloseButton.click();
    dynamicContent.waitTillDynamicContentCreationFlag();
  });
  
  it("Search and delete newly added dynamic content", () => {
    dynamicContent.waitforPageLoad();
    cy.visit('/s/dwc?search='+ dynamicContentText);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
});
