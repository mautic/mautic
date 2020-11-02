/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const dynamicContent = require("../../Pages/DynamicContent");
const search=require("../../Pages/Search");

var dynamicContentText = "testDynamicContent";
context("Create dynamic content", () => {

  it("Create new dynamic content", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.dynamicContentSection.click();
    dynamicContent.waitforPageLoad();
    dynamicContent.createNewContent.click()
    dynamicContent.waitforCreationPageLoaded();
    dynamicContent.dynamicContentName.type(dynamicContentText);
    dynamicContent.typeContent.type("Test demo Content");
    search.saveAndCloseButton.click();
    dynamicContent.waitTillDynamicContentCreationFlag();
  });

  it("Edit newly added dynamic content", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.dynamicContentSection.click();
    dynamicContent.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type(dynamicContentText);
    dynamicContent.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    dynamicContent.clickOnFirstSearchedElement.contains(dynamicContentText).click();
    dynamicContent.waitTillDynamicContentCreationFlag();
    dynamicContent.editSelectedContent.click();
    dynamicContent.typeContent.clear();
    dynamicContent.typeContent.type("Test Demo Content");
    search.saveAndCloseButton.click();
    dynamicContent.waitTillDynamicContentCreationFlag();
  });
  
  it("Search and delete newly added dynamic content", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.dynamicContentSection.click();
    dynamicContent.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type(dynamicContentText);
    dynamicContent.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
});
