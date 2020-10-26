/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const stages = require("../../Pages/Stages");
const search = require("../../Pages/Search");

context("Add new a stage", () => {
   it("Add a Stage", () => {
    leftNavigation.StagesSection.click();
    cy.wait(2000);
    stages.addNewButton.click();
    stages.stageName.type("TestStage");
    stages.stageWeight.type("40");
    stages.saveAndCloseButton.click();
  })

  it("Edit newly added Stage", () => {
    leftNavigation.StagesSection.click();
    search.searchBox.clear();
    search.searchBox.type("TestStage");
    cy.wait(2000);
    stages.searchAndClickOnFirstElement.contains("Test").click();
    stages.waitforPageLoad;
    stages.stageWeight.clear();
    stages.stageWeight.type("50");
    stages.saveAndCloseButton.click();
    cy.wait(1000);
  })

  it("search and delete newly added stage", () => {
    leftNavigation.StagesSection.click();
    search.searchBox.clear();
    search.searchBox.type("TestStage");
    cy.wait(2000);
    stages.searchAndClickOnFirstCheckbox.click();
    search.selectAndClickFirstItemsOption.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  })

  });


