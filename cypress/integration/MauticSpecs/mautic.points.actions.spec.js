/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const points = require("../../Pages/Points");
const emails = require("../../Pages/Emails"); // Community Specific
const search = require("../../Pages/Search"); // Community Specific


var testAction = "testAction";

context("Verify that user is able to create and edit points action", () => {
  beforeEach("Visit HomePage", () => {
    cy.visit("s/points");
  });

  it("Add a Test Email", () => { // Community Specific
  cy.visit('s/emails')
  emails.waitforPageLoad();
  emails.addNewButton.click({ force: true });
  emails.waitforEmailSelectorPageGetsLoaded();
  emails.templateEmailSelector.click();
  emails.emailSubject.type("Test Email");
  emails.emailInternalName.type("Test Email");
  emails.saveEmailButton.click();
  emails.closeButton.click();
  emails.waitforEmailCreation();
  });

  it("Add a Action", () => {
    points.waitforActionPageLoad();
    points.addNewActionButton.click();
    points.actionName.type(testAction);
    points.pointsToBeChanged.clear(); // Community Specific
    points.pointsToBeChanged.type("40");
    points.actionDropDown.click();
    points.opensAnEmailOption.click();
    points.waitTillSelectEmail();
    points.clickOnTextbox.click();
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

  it("Delete a newly added email", () => { // Community Specific
    cy.visit('s/emails');
    emails.waitforPageLoad();
    cy.visit('/s/emails?search=Test')
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });


});
