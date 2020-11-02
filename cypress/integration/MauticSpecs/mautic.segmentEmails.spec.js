/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const emails = require("../../Pages/Emails");
const search=require("../../Pages/Search");
const email = require("../../Pages/Emails");

context("Emails", () => {

  it("Add new segment Email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click({ force: true });
    emails.waitforPageLoad();
    emails.addNewButton.click({ force: true });
    cy.wait(2000);
    emails.segmentEmailSelector.click();
    cy.wait(2000);
    emails.emailSubject.type('testSegmentEmailCypress');
    emails.emailInternalName.type('testSegmentEmailCypress')
    emails.contactSegmentSelector.click();
    emails.firstSegmentEmailSelector.click();
    emails.saveEmailButton.click();
    emails.closeButton.click();
    emails.waitforEmailCreation();
    search.searchBox.clear();
    search.searchBox.type("testSegmentEmailCypress");
    cy.wait(2000);
    emails.searchAndSelectEmail.contains('testSegmentEmailCypress').click();
    cy.wait(2000);
    emails.scheduleSegmentEmail.click();
    emails.scheduleButton.click();
    cy.wait(5000);
  });

  it("Search and Delete newly added segment Email", () => {
    leftNavigation.ChannelsSection.click();
    leftNavigation.EmailsSubSection.click();
    cy.wait(3000);
    emails.waitforPageLoad();
    search.searchBox.clear();
    search.searchBox.type("testSegmentEmailCypress");
    cy.wait(2000);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });

  });


