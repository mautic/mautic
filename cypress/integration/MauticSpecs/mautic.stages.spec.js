/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const stages = require("../../Pages/Stages");
const search = require("../../Pages/Search");

var testStage = "TestStage";

context("Verify that user is able to create and delete stages", () => {
  beforeEach("Visit HomePage", () => {
    cy.visit("s/stages");
  });

  it("Add a Stage", () => {
    stages.checkNoResultFoundMessage.should("contain", "No Results Found");
    stages.addNewButton.click();
    stages.stageName.type(testStage);
    stages.stageWeight.type("40");
    stages.saveAndCloseButton.click();
    stages.waitforStageCreation();
  });

  it("Edit newly added Stage", () => {
    cy.visit("/s/stages?search=" + testStage);
    stages.searchAndClickOnFirstElement.contains(testStage).click();
    stages.waitforPageLoad;
    stages.stageWeight.clear();
    stages.stageWeight.type("50");
    stages.saveAndCloseButton.click();
    stages.waitforStageCreation();
  });

  it("search and delete newly added stage", () => {
    cy.visit("/s/stages?search=" + testStage);
    stages.searchAndClickOnFirstCheckbox.click();
    search.selectAndClickFirstItemsOption.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    stages.checkNoResultFoundMessage.should("contain", "No Results Found");
  });
});
