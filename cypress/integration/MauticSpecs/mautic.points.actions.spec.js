/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const points = require("../../Pages/Points");

var testAction = "testAction";

context("Verify that user is able to create and edit points action", () => {
  beforeEach("Visit HomePage", () => {
    cy.visit("s/points");
  });

  it("Add a Action", () => {
    points.waitforActionPageLoad();
    points.addNewActionButton.click();
    points.actionName.type(testAction);
    points.pointsToBeChanged.type("40");
    points.actionDropDown.click();
    points.opensAnEmailOption.click();
    points.waitTillSelectEmail();
    points.clickOnTextbox.click();
    points.typeEmailName.type("Test");
    points.selectSearchedEmail.click();
    points.saveAndCloseButton.click();
    points.waitforActionToBeCreated();
  });

  it("Edit a newly added action", () => {
    cy.visit("/s/points?search=" + testAction);
    points.searchAndGetFirstResult.click();
    points.pointsToBeChanged.clear().type("10");
    points.saveAndCloseButton.click();
    points.waitforActionToBeCreated();
  });

  it("Delete a newly added action", () => {
    cy.visit("/s/points?search=" + testAction);
    points.searchAndSelectFirstCheckBox.click();
    points.editOptionsForFirstSelection.click();
    points.deleteOption.click();
    points.confirmWindowDelete.click();
    cy.wait(1000);
    points.checkNoResultFoundMessage.should("contain", "No Results Found");
  });

  
});
