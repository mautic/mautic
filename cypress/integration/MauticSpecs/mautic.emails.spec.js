/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const emails = require("../../Pages/Emails");
const search=require("../../Pages/Search");

context("Emails", () => {

  it("Add new Email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    emails.waitforPageLoad();
    emails.addNewButton.click({ force: true });
    emails.waitforEmailSelectorPageGetsLoaded();
    emails.templateEmailSelector.click();
    cy.wait(2000);
    emails.emailSubject.type('TestEmailCypress');
    emails.emailInternalName.type('TestEmailCypress')
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
    emails.waitforEmailCreation();
  });

  it("Edit newly added email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    emails.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type("TestEmailCypress");
    emails.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    emails.searchAndSelectEmail.contains("TestEmailCypress").click();
    emails.waitTillEditMailPageGetsVisible();
    emails.emailEditButton.click();
    emails.waitforSelectedEmailGetsOpen();
    emails.emailSubject.clear();
    emails.emailSubject.type('TestEmail');
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
    emails.waitforEmailUpdate();
  });

  it("Search and delete newly added email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    emails.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type("TestEmailCypress");
    emails.waitTillSearchedElementGetsVisible();
    cy.wait(1000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    emails.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  });


