/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const settings = require("../../Pages/Settings");
const customFields = require("../../Pages/CustomFields");
const contact = require("../../Pages/Contacts");
const leftNavigation = require("../../Pages/LeftNavigation");
const company = require("../../Pages/Company");
const search = require("../../Pages/Search");

var customFieldForContact = "Custom field for Contact"
var customFieldForCompany = "Custom field for Company"
var customFieldCompanyName = "Company with custom field"

context("Custom Fields", () => {
   
  it("add new custom field for Company", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click({force: true});
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(customFieldForCompany);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Company",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Boolean",{force: true});
    customFields.SaveAndCloseButton.click();
    cy.wait(3000);
  })

  it("add new custom field for Contact", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click({force: true});
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(customFieldForContact);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Boolean",{force: true});
    customFields.SaveAndCloseButton.click();
    cy.wait(3000);
  });

  it("Verify that created custom field is available in contact creation", () => {
    leftNavigation.contactsSection.click();
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.createdCustomFieldIsDisplayed.should('contain', customFieldForContact)
  });

  it("Verify that company is getting created with custom field", () => {
    leftNavigation.companySection.click();
    company.waitforPageLoad();
    company.addNewButton.click({ force: true });
    company.createdCustomFieldIsDisplayed.should('contain', customFieldForCompany)
    company.selectYesForCompanyLabel.click()
    company.companyName.type(customFieldCompanyName);
    company.saveButton.click();
    cy.get('.alert-growl').should('contain', customFieldCompanyName +' has been created!');
  });

  it("Search and Delete campany with custom field", () => {
    leftNavigation.companySection.click();
    company.waitforPageLoad();
    cy.visit('/s/companies?search=Company');
    company.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });

  it("Delete the created custom fields", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click({force: true});
    customFields.waitforPageLoad();
    cy.visit('/s/contacts/fields?search=Custom');
    customFields.selectAllCustomField.click();
    customFields.clickOnDropdownToDelete.click();
    customFields.deleteSelectedCustomField.click();
    customFields.waitTillConfirmationWindowGetsLoaded();
    customFields.confirmationWindowForDelete.click();
    customFields.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  });
