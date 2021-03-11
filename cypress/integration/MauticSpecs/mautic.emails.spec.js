/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const emails = require("../../Pages/Emails");
const search=require("../../Pages/Search");

var testEmailCypress = "TestEmailCypress";

context("Verify that user is able to create and edit email", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/emails");
  });

  it("Add new Email", () => {
    emails.waitforPageLoad();
    emails.addNewButton.click({ force: true });
    emails.waitforEmailSelectorPageGetsLoaded();
    emails.templateEmailSelector.click();
    emails.emailSubject.type(testEmailCypress);
    emails.emailInternalName.type(testEmailCypress)
    emails.saveEmailButton.click();
    emails.waitforEmailCreation();
    emails.closeButton.click({force: true});
  });

  it("Edit newly added email", () => {
    emails.waitforPageLoad();
    cy.visit('/s/emails?search=' + testEmailCypress)
    emails.searchAndSelectEmail.contains(testEmailCypress).click();
    emails.waitTillEditMailPageGetsVisible();
    emails.emailEditButton.click();
    emails.waitforSelectedEmailGetsOpen();
    emails.emailSubject.clear();
    emails.emailSubject.type('TestEmail');
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
    emails.waitforEmailCreation();
  });

  it("Search and delete newly added email", () => {
    emails.waitforPageLoad();
    cy.visit('/s/emails?search=' + testEmailCypress)
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    emails.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  });


