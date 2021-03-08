/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const company = require("../../Pages/Company");
const search = require("../../Pages/Search");

var companyName = "CompanyAddedByCypress";

context("Verify that user is able to create and delete company", () => {
  beforeEach("Visit HomePage", () => {
    cy.visit("s/companies");
  });

  it("Add new Company", () => {
    leftNavigation.companySection.click();
    company.waitforPageLoad();
    company.addNewButton.click({ force: true });
    company.companyName.type(companyName);
    company.saveButton.click();
    company.alertMessage.should(
      "contain",
      "CompanyAddedByCypress has been created!"
    );
  });

  it("Edit newly added Company", () => {
    leftNavigation.companySection.click();
    company.waitforPageLoad();
    cy.visit("/s/companies?search=" + companyName);
    company.clickCompanyEdit.click(); //Community specific
    company.editCompany.click(); //Community specific
    company.companyCity.type("Pune");
    company.companyZipCode.type("412308");
    company.saveButton.click();
    company.waitforCompanyCreation();
  });

  it("Search and Delete Company", () => {
    leftNavigation.companySection.click();
    company.waitforPageLoad();
    cy.visit("/s/companies?search=" + companyName);
    company.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
});
