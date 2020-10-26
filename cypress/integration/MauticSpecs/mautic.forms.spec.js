/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const forms = require("../../Pages/Forms");
const form = require("../../Pages/Forms");

context("Create Form", () => {
  it("Create a New Form", () => {
    leftNavigation.componentsSection.click();
    leftNavigation.formsSubSection.click();
    cy.wait(2000);
    forms.waitforPageLoad();
    forms.addNewButton.click();
    cy.wait(1000);
    forms.standaloneFormSelector.click();
    cy.wait(1000);
    forms.formName.type("Test Form");
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
    form.fieldLabel.type("Email");
    form.contactFieldTab.click();
    form.contactFieldDropdown.click();
    form.contactFieldSearchBox.type("Email");
    form.contactFieldSearchFirstResult.click();
    form.addFieldButton.click();
    form.saveFormButton.click();
  });
});
