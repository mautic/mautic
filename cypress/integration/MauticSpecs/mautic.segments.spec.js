/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const segments = require("../../Pages/Segments");
const segment = require("../../Pages/Segments");

context("Segments", () => {
  it("Add new Segment", () => {
    leftNavigation.SegmentsSection.click();
    cy.wait(1000);
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    cy.wait(1000);
    segments.segmentName.type("Cypress");
    segments.filterTab.click();
    cy.wait(1000);
    segments.filterDropDown.click();
    cy.wait(1000);
    segments.filterSearchBox.type("First");
    segments.filterField.click();
    segments.filterValue.type("Cypress");
    segments.saveAndCloseButton.click();
  });

  it("Edit newly added segment", () => {
    leftNavigation.SegmentsSection.click();
    cy.wait(1000);
    segments.waitForPageLoad();
    segments.SearchBox.type("Cypress");
    cy.wait(1000);
    segments.searchAndSelectSegment.contains("Cypress").click();
    cy.wait(2000);
    segment.editSegment.click();
    segments.filterTab.click();
    cy.wait(1000);
    segments.filterDropDown.click();
    cy.wait(1000);
    segments.filterSearchBox.type("Last name");
    segments.filterField.click();
    segments.leadListFilter.select("or");
    segments.secondFilterTextBox.type("Test");
    segments.saveAndCloseButton.click();
  });

  it("Search and Delete Segment", () => {
    leftNavigation.SegmentsSection.click();
    cy.wait(1000);
    segments.waitForPageLoad();
    segments.SearchBox.click().clear();
    segments.SearchBox.type("Cypress");
    cy.wait(1000);
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segment.deleteConfirmation.click();
  });
});
