/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const form = require("../../Pages/Forms");
const search=require("../../Pages/Search");

var testFormName= "testForm";
context("Verify that user is able to create and edit forms", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/forms");
  });

  it("Create a new form", () => {
    form.waitforPageLoad();
    form.addNewButton.click();
    form.waitTillFormOptionsGetsLoaded()
    form.standaloneFormSelector.click();
    cy.wait(1000);
    form.formName.type(testFormName);
    cy.wait(1000);

    form.fieldsTab.click();
    form.fieldTypeDropDown.click();
    form.fieldTypeSearch.type('Text');
    form.firstResultOfFieldTypeSearch.click();
    form.fieldLabel.type("Title");
    form.contactFieldTab.click({force: true});
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
    form.waitforPageLoad();
    cy.visit('/s/forms?search=' + testFormName)
    form.searchAndSelectFirstItem.contains(testFormName).click();
    form.waitTillCreatedFormGetsLoaded();
    form.editForm.click();
    form.waitTillCreatedFormGetsOpen();

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
    form.waitforPageLoad();
    cy.visit('/s/forms?search=' + testFormName)
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    search.checkNoResultFoundMessage.should('contain','No Results Found');
  });

});
