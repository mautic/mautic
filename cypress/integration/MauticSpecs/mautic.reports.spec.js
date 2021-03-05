/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const reports = require("../../Pages/Reports");
const search=require("../../Pages/Search");

var testReport = "testReport";

context("Verify that user is able to create and view created report", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/reports");
  });

  it("Create a new report", () => {
    reports.waitforPageLoad();
    reports.createNewReport.click();
    reports.waitforNewReportsPage();
    reports.reportName.type(testReport);
    reports.ownerName.click();
    reports.chooseOwnersName.click();
    reports.clickOnDataSource.click()
    reports.enterAndSelectDataSource.type("Contacts")
    reports.selectSearchedDataSource.click();
    reports.waitTillSelectedDataSourceGetsActive()
    reports.dataTab.click();
    reports.columnTextBox.type("First Name");
    reports.selectColumnFromSearch.contains("First Name").click();
    reports.waitTillSelectedItemGetsAdded();
    reports.columnTextBox.clear();
    reports.columnTextBox.type("City");
    reports.selectColumnFromSearch.contains("City").click();
    reports.waitTillSelectedItemGetsAdded();
    reports.columnTextBox.clear();
    reports.columnTextBox.type("ID");
    reports.selectColumnFromSearch.contains("Contact ID").click();
    reports.waitTillSelectedItemGetsAdded();
    reports.saveAndClose.click();
    reports.waitforReportCreation();
  })

  it("Edit newly added report", () => {
    reports.waitforPageLoad();
    cy.visit('/s/reports?search='+ testReport);
    reports.clickOnFirstelementSearched.contains(testReport).click();
    reports.waitTillCreatedReportOpen();
    reports.editeport.click();
    reports.dataTab.click();
    reports.columnTextBox.type("Date Last Active");
    reports.selectColumnFromSearch.contains("Date Last Active").click();
    reports.saveAndClose.click();
    reports.waitforReportCreation();
  })
  
  it("Search and delete newly report", () => {
    reports.waitforPageLoad();
    cy.visit('/s/reports?search='+ testReport);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    reports.checkNoResultFoundMessage.should('contain','No Results Found');
  })
});
