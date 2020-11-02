/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const points = require("../../Pages/Points");
const search = require("../../Pages/Search");

context("Points", () => {
   it("Add a Action", () => {
    leftNavigation.PointsSection.click();
    points.manageActionSection.click();
    cy.contains('Manage Actions').click()
    cy.wait(1000);
    points.waitforActionPageLoad();
    points.addNewActionButton.click();
    points.actionName.type("Action");
    points.pointsToBeChanged.type("40");
    points.actionDropDown.click();
    points.opensAnEmailOption.click();
    points.saveAndCloseButton.click();
    points.waitforActionToBeCreated;
  })

  it("Edit a newly added action", () => {
    leftNavigation.PointsSection.click();
    points.manageActionSection.click();
    search.searchBox.clear();
    search.searchBox.type("Action");
    cy.wait(1000);
    points.searchAndGetFirstResult.click();
    points.pointsToBeChanged.clear().type("10");
    points.saveAndCloseButton.click();
    points.waitforActionToBeCreated;
  })

  it("Delete a newly added action", () => {
    leftNavigation.PointsSection.click();
    points.manageActionSection.click();
    search.searchBox.clear();
    search.searchBox.type("Action");
    cy.wait(1000);
    points.searchAndSelectFirstCheckBox.click();
    points.editOptionsForFirstSelection.click();
    points.deleteOption.click();
    points.confirmWindowDelete.click();
    cy.wait(1000);
    points.checkNoResultFoundMessage.should('contain','No Results Found');
  })

  it("Add a Trigger", () => {
    leftNavigation.PointsSection.click();
    points.manageTriggerSection.click();
    cy.contains('Manage Triggers').click()
    cy.wait(1000);
    points.waitforPointTriggerPageLoad();
    points.addNewPointsTriggerButton.click();
    points.triggerName.type("Action");
    points.triggerPoints.type("40");
    points.publishTrigger.click();
    points.eventsTab.click();
    points.addEventButton.click();
    points.sendEmailEvent.click();
    cy.wait(1000);
    points.eventName.type("Test Trigger");
    points.emailSelector.click();
    points.firstEmail.contains("Test").click();
    points.addButton.click();
    cy.wait(1000);
    points.saveAndCloseTriggerButton.click();
    points.waitforTriggerToBeCreated;
  })

  it("Edit newly added Trigger", () => {
    leftNavigation.PointsSection.click();
    points.manageTriggerSection.click();
    cy.contains('Manage Triggers').click()
    cy.wait(1000);
    points.waitforPointTriggerPageLoad();
    search.searchBox.clear();
    search.searchBox.type("Action");
    cy.wait(1000);
    points.searchAndGetFirstResultTriggerTable.contains("Action").click();
    points.triggerPoints.clear();
    points.triggerPoints.type("50");
    points.saveAndCloseTriggerButton.click();
    points.waitforTriggerToBeCreated;
  })

  it("Delete newly added Trigger", () => {
    leftNavigation.PointsSection.click();
    points.manageTriggerSection.click();
    cy.contains('Manage Triggers').click()
    cy.wait(1000);
    points.waitforPointTriggerPageLoad();
    search.searchBox.clear();
    search.searchBox.type("Action");
    cy.wait(1000);
    points.searchAndSelectFirstCheckBoxForTrigger.click();
    points.editOptionsForFirstSelectionForTrigger.click();
    points.deleteOption.click();
    points.confirmWindowDelete.click();
    cy.wait(1000);
    points.checkNoResultFoundMessage.should('contain','No Results Found');
  })
  });


