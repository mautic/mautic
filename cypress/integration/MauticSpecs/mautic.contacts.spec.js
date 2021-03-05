/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const contact = require("../../Pages/Contacts");
const search = require("../../Pages/Search");

var contactName = "ContactAddedCypress";

context("Verify that user is able to create and delete the contacts", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/contacts");
  });

  it("Add new Contact", () => {
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactName);
    contact.lastName.type("Tester");
    contact.leadEmail.type("Cypress@test.com");
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Edit newly added contact", () => {
    contact.waitforPageLoad();
    cy.visit('/s/contacts?search=' + contactName);
    contact.searchAndClickForFirstElement.contains(contactName).click();
    contact.editContact.click();
    contact.waitForContactEditPageOpen();
    contact.leadCity.type("Pune");
    contact.lastName.clear().type("Contact");
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Search and delete newly added contact", () => {
    contact.waitforPageLoad();
    cy.visit('/s/contacts?search=' + contactName);
    contact.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });
  
  it("import new Contacts", () => {
    contact.waitforPageLoad();
    contact.importExportDropdownMenu.click({ force: true });
    contact.importButton.click({ force: true });
    const fileName = "contacts_july-22-2020.csv";
    const fileType = "application/csv";
    const fileInput = "input[type=file]";
    cy.upload_file(fileName, fileType, fileInput);
    cy.get('[name="lead_import[start]"]').click();
    cy.get(
      "#lead_field_import_company_chosen > .chosen-single > span > .group-name"
    ).click();
    cy.get("#lead_field_import_company").select("Company Name", {
      force: true,
    });
    cy.get("#lead_field_import_buttons_save_toolbar").click();
  });
});
