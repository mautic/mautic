/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const reports = require("../../Pages/Reports");
const search=require("../../Pages/Search");

var testReport = "testReport";

context("Create a report", () => {

  it("Create a new report", () => {
    leftNavigation.reportSection.click();
    reports.waitforPageLoad();
    reports.createNewReport.click();
    reports.waitforNewReportsPage();
    reports.reportName.type(testReport);
    reports.ownerName.click();
    reports.chooseOwnersName.click();
    reports.dataTab.click();
    reports.columnTextBox.type("created by");
    reports.selectColumnFromSearch.contains("Created").click();
    reports.waitTillSelectedItemGetsAdded();
    reports.columnTextBox.clear();
    reports.columnTextBox.type("Date created");
    reports.selectColumnFromSearch.contains("Date").click();
    reports.waitTillSelectedItemGetsAdded();
    reports.saveAndClose.click();
    reports.waitforReportCreation();
  })

  it("Edit newly added report", () => {
    leftNavigation.reportSection.click();
    reports.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type(testReport);
    reports.waitTillSearchItemGetsVisible();
    cy.wait(1000);
    reports.clickOnFirstelementSearched.contains(testReport).click();
    reports.waitTillCreatedReportOpen();
    reports.editeport.click();
    reports.dataTab.click();
    reports.columnTextBox.type("Date last");
    reports.selectColumnFromSearch.contains("Date last").click();
    reports.saveAndClose.click();
    reports.waitforReportCreation();
  })
  
  it("Search and delete newly report", () => {
    leftNavigation.reportSection.click();
    reports.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type(testReport);
    reports.waitTillSearchItemGetsVisible();
    cy.wait(1000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    reports.checkNoResultFoundMessage.should('contain','No Results Found');
    cy.wait(3000);
  })
});
