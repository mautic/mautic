/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const segments = require("../../Pages/Segments");
const search = require("../../Pages/Search");

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
    segments.waitforSegmentCreation();
  });

  it("Edit newly added segment", () => {
    leftNavigation.SegmentsSection.click();
    segments.waitForPageLoad();
    search.searchBox.clear();
    segments.SearchBox.type("Cypress");
    segments.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    segments.searchAndSelectSegment.contains("Cypress").click();
    segments.waitTillClickedSegmentGetsOpen();
    segments.editSegment.click();
    segments.filterTab.click();
    cy.wait(1000);
    segments.filterDropDown.click();
    cy.wait(1000);
    segments.filterSearchBox.type("Last name");
    segments.filterField.click();
    cy.wait(1000);
    segments.leadListFilter.select("or");
    cy.wait(1000);
    segments.secondFilterTextBox.type("Test");
    segments.saveAndCloseButton.click();
    segments.waitforSegmentUpdate();
  });

  it("Search and Delete Segment", () => {
    leftNavigation.SegmentsSection.click();
    segments.waitForPageLoad();
    segments.SearchBox.click().clear();
    segments.SearchBox.type("Cypress");
    segments.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });
});
