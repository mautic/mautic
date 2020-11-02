/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const form = require("../../Pages/Forms");
const search=require("../../Pages/Search");

var testFormName= "testForm";
context("Create Form", () => {

  it("Create a new form", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.formsSubSection.click();
    cy.wait(2000);
    form.waitforPageLoad();
    form.addNewButton.click();
    cy.wait(1000);
    form.standaloneFormSelector.click();
    cy.wait(1000);
    form.formName.type(testFormName);
    cy.wait(1000);

    form.fieldsTab.click();
    form.fieldTypeDropDown.click();
    form.fieldTypeSearch.type('Text');
    form.firstResultOfFieldTypeSearch.click();
    form.fieldLabel.type("Title");
    form.contactFieldTab.click();
    form.contactFieldDropdown.click();
    form.contactFieldSearchBox.click().type("Title");
    form.contactFieldSearchFirstResult.click();
    form.addFieldButton.click();
   
    form.fieldsTab.click();
    form.fieldTypeDropDown.click();
    form.fieldTypeSearch.type('Email');
    form.firstResultOfFieldTypeSearch.click();
    cy.wait(1000);
    form.fieldLabel.type("Email");
    form.contactFieldTab.click();
    form.addFieldButton.click();
    form.saveFormButton.click();
    form.waitforFormCreation();
  });

  it("Edit newly added form", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.formsSubSection.click();
    cy.wait(1000);
    search.searchForm.clear();
    search.searchForm.type(testFormName);
    cy.wait(1000);
    form.searchAndSelectFirstItem.contains(testFormName).click();
    cy.wait(2000);
    form.editForm.click();
    cy.wait(1000);

    form.fieldsTab.click();
    form.fieldTypeDropDown.click();
    form.fieldTypeSearch.type('Text');
    form.firstResultOfFieldTypeSearch.click();
    form.fieldLabel.type("Living city");
    form.contactFieldTab.click();
    form.contactFieldTab.click();
    form.contactFieldDropdown.click();
    form.contactFieldSearchBox.click().type("City");
    form.contactFieldSearchFirstResult.click();
    form.addFieldButton.click();
    form.saveFormButton.click();
    form.waitforFormCreation();
  });

  it("Search and delete newly added form", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.formsSubSection.click();
    cy.wait(1000);
    search.searchForm.clear();
    search.searchForm.type(testFormName);
    cy.wait(2000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    search.checkNoResultFoundMessage.should('contain','No Results Found');
  });

});
