// ***********************************************************
// This example support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
const leftNavigation = require("../Pages/LeftNavigation");
const search = require("../Pages/Search");
const contact = require("../Pages/Contacts");
const emails = require("../Pages/Emails");
const segments = require("../Pages/Segments");
const segment = require("../Pages/Segments");
import "./commands";

Cypress.Cookies.defaults({
  preserve: [Cypress.env("instanceId"), "sid", "success"],
});

before("Perform login", () => {
  cy.visit("/s/login");
  cy.login(Cypress.env("userName"), Cypress.env("password"));

  // adding sample contacts to be used across test
  leftNavigation.contactsSection.click();
  contact.waitforPageLoad();
  contact.addNewButton.click({ force: true });
  contact.title.type("Mr");
  contact.firstName.type("Test");
  contact.lastName.type("User1");
  contact.leadEmail.type("Testuser1@mailtest.mautic.com");
  contact.SaveButton.click();
  contact.closeButton.click({ force: true });

  //adding sample email to be used across test

  leftNavigation.ChannelsSection.click();
  leftNavigation.EmailsSubSection.click();
  cy.wait(3000);
  emails.waitforPageLoad();
  emails.addNewButton.click({ force: true });
  cy.wait(5000);
  emails.templateEmailSelector.click();
  cy.wait(2000);
  emails.emailSubject.type("Test Email");
  emails.emailInternalName.type("Test Email");
  emails.saveEmailButton.click();
  emails.closeButton.click({ force: true });

  //adding sample segment to be used across test
  leftNavigation.SegmentsSection.click();
  cy.wait(1000);
  segments.waitForPageLoad();
  segments.addNewButton.click({ force: true });
  cy.wait(1000);
  segments.segmentName.type("TestUsers");
  segments.filterTab.click();
  cy.wait(1000);
  segments.filterDropDown.click();
  cy.wait(1000);
  segments.filterSearchBox.type("First");
  segments.filterField.click();
  segments.filterValue.type("Test");
  segments.saveAndCloseButton.click();
});

after("Delete Test Data", () => {
  //deleting created contact
  cy.wait(2000);
  leftNavigation.contactsSection.click();
  contact.waitforPageLoad();
  search.searchBox.clear();
  search.searchBox.type("User1");
  cy.wait(2000);
  search.selectCheckBoxForFirstItem.click({ force: true });
  search.OptionsDropdownForFirstItem.click();
  search.deleteButtonForFirstItem.click();
  search.confirmDeleteButton.click();

  //deleting created Email
  leftNavigation.ChannelsSection.click();
  leftNavigation.EmailsSubSection.click();
  emails.waitforPageLoad();
  search.searchBox.clear();
  search.searchBox.type("Test");
  cy.wait(2000);
  search.selectCheckBoxForFirstItem.click({ force: true });
  cy.wait(2000);
  search.OptionsDropdownForFirstItem.click();
  search.deleteButtonForFirstItem.click();
  search.confirmDeleteButton.click();
  cy.wait(1000);

  //deleting created segment
  leftNavigation.SegmentsSection.click();
  cy.wait(1000);
  segments.waitForPageLoad();
  segments.SearchBox.click().clear();
  segments.SearchBox.type("TestUsers");
  cy.wait(2000);
  segments.firstCheckbox.click();
  segments.firstDropDown.click();
  segments.deleteOption.click();
  segment.deleteConfirmation.click();
});
beforeEach("Visit HomePage", () => {
  cy.visit("");
});

Cypress.on("uncaught:exception", (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test
  return false;
});

// Alternatively you can use CommonJS syntax:
// require('./commands')
