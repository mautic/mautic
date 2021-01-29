/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const assets = require("../../Pages/Assets");
const search = require("../../Pages/Search");

context("Assets", () => {
  
  it("Add new local asset", () => {
    cy.visit("/s/assets");
    assets.addNewButton.click();
    const fileName = "Test.png";
    assets.assetTitle.type("Local Asset");
    cy.get("#dropzone").attachFile(fileName, { subjectType: 'drag-n-drop' }).then((loc) => {
    cy.wait(2000);
    assets.saveAndCloseButton.click();
    assets.closeButton.should("be.visible");
    });
  });

  it("Edit existing local asset", () => {
    cy.visit("/s/assets");
    cy.visit("/s/assets?search=Local");
    assets.waitTillSearchResultGetsDisplayed();
    assets.searchAndClickForFirstElement.contains("Local").click();
    assets.editAsset.click();
    assets.assetTitle.clear();
    assets.assetTitle.type("Local Asset Updated");
    // To Do : Discuss, why ? Test Fails if we use this step
    // assets.remoteurl.type(remoteUrl);
    assets.saveAndCloseButton.click();
    assets.closeButton.should("be.visible");
  });

  it("Search and Delete local Asset", () => {
    cy.visit("/s/assets");
    cy.visit("/s/assets?search=Local");
    assets.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
  
  
  it("Add new remote asset", () => {
    const remoteUrl =
      "https://docs.google.com/spreadsheets/d/1WXr9byp_l3QWpNgSIczXtL_udVNyq5FzIhySe5W9PiI/edit?usp=sharing";
    cy.visit("/s/assets");
    assets.addNewButton.click();
    assets.remoteButton.click();
    assets.assetTitle.type("Remote Asset");
    assets.remoteurl.type(remoteUrl);
    assets.saveAndCloseButton.click();
    assets.closeButton.should("be.visible");
  });


  it("Edit  existing remote asset", () => {
    cy.visit("/s/assets");
    cy.visit("/s/assets?search=remote");
    assets.waitTillSearchResultGetsDisplayed();
    assets.searchAndClickForFirstElement.contains("remote").click();
    assets.editAsset.click();
    assets.assetTitle.clear();
    assets.assetTitle.type("Remote Asset Updated");
    // To Do : Discuss, why ? Test Fails if we use this step
    // assets.remoteurl.type(remoteUrl);
    assets.saveAndCloseButton.click();
    assets.closeButton.should("be.visible");
  });

  it("Search and Delete remote Asset", () => {
    cy.visit("/s/assets");
    cy.visit("/s/assets?search=remote");
    assets.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
});
