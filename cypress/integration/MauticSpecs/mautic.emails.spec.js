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
    cy.wait(3000);
    emails.waitforPageLoad();
    emails.addNewButton.click({ force: true });
    cy.wait(5000);
    emails.templateEmailSelector.click();
    cy.wait(2000);
    emails.emailSubject.type('TestEmailCypress');
    emails.emailInternalName.type('TestEmailCypress')
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
    cy.wait(1000);
  });

  it("Edit newly added email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    emails.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type("TestEmailCypress");
    cy.wait(2000);
    emails.searchAndSelectEmail.contains("TestEmailCypress").click();
    cy.wait(1000);
    emails.emailEditButton.click();
    cy.wait(1000);
    emails.emailSubject.clear();
    emails.emailSubject.type('TestEmail');
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
  });

  it("Search and delete newly added email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    emails.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type("TestEmailCypress");
    cy.wait(2000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    cy.wait(1000);
  });

  });


