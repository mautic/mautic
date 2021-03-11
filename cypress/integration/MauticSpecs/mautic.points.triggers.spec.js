/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const points = require("../../Pages/Points");
const emails = require("../../Pages/Emails");
const contact = require("../../Pages/Contacts");
const search = require("../../Pages/Search");

var testTrigger = "testTrigger";
var testTriggerEmail = "TestTriggerEmail";
var testContact = "testcontact";
var mailtestUrl = "https://mailtest.mautic.com/api/v1/mailbox/";
var getMailId;

context("Verify that user is able to create trigger and verify that user has received an email once trigger condition is matched", () => {
  beforeEach("Visit HomePage", () => {
    cy.visit("s/points/triggers");
  });

  it("Add new trigger Email", () => {
    cy.visit("s/emails");
    emails.waitforPageLoad();
    emails.addNewButton.click({ force: true });
    emails.waitforEmailSelectorPageGetsLoaded();
    emails.templateEmailSelector.click();
    emails.emailSubject.type(testTriggerEmail);
    emails.emailInternalName.type(testTriggerEmail)
    emails.saveEmailButton.click();
    emails.closeButton.click({force: true});
    emails.waitforEmailCreation();
  });
  
  it("Add a Trigger", () => {
    points.waitforPointTriggerPageLoad();
    points.addNewPointsTriggerButton.click();
    points.triggerName.type(testTrigger);
    points.triggerPoints.type("40");
    points.publishTrigger.click();
    points.eventsTab.click();
    points.addEventButton.click();
    points.sendEmailEvent.click();
    cy.wait(1000);
    points.eventName.type(testTrigger);
    points.emailSelector.click();
    points.firstEmail.contains(testTriggerEmail).click();
    points.addButton.click();
    cy.wait(1000);
    points.saveAndCloseTriggerButton.click();
    points.waitforTriggerToBeCreated();
  });

  it("Edit newly added Trigger", () => {
    points.waitforPointTriggerPageLoad();
    cy.visit("/s/points/triggers?search=" + testTrigger);
    points.searchAndGetFirstResultTriggerTable.contains(testTrigger).click();
    points.triggerPoints.clear();
    points.triggerPoints.type("50");
    points.saveAndCloseTriggerButton.click();
    points.waitforTriggerToBeCreated();
  });

  it("Edit previously added contact to point 50 to test the trigger", () => {
    cy.visit('s/contacts');
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(testContact);
    contact.lastName.type("Data");
    contact.leadEmail.type(testContact +"@mailtest.mautic.com");
    contact.SaveButton.click();
    points.waitForContactUpdate();
    contact.closeButton.click(); // Community Specific
    cy.visit("/s/contacts?search=" + testContact);
    contact.searchAndClickForFirstElement.contains(testContact).click();
    contact.editContact.click();
    contact.waitForContactEditPageOpen();
    contact.updateContactPoints.clear().type("50");
    contact.SaveButton.click();
    points.waitForContactCreation(); // Community Specific
    contact.closeButton.click({ force: true });
    cy.wait(5000);
  });

  it("Verify that user has received the trigger Email", () => {
    cy.wait(5000);
    cy.request({
      method: "GET",
      url: mailtestUrl + testContact,
    }).then(function (response) {
      expect(response).to.have.property("status", 200);
      expect(response.body).to.not.be.null;
      expect(response.body[0]).to.have.property("mailbox", testContact);
      expect(response.body[0]).to.have.property("subject", testTriggerEmail);
      expect(response.body[0]).to.have.property("seen", false);
      const body = response.body[0];
      getMailId = body["id"];
    });
  });

  it("Delete the read email", () => {
    cy.request({
      method: "DELETE",
      url: mailtestUrl + testContact + "/" + getMailId,
    }).then(function (response) {
      expect(response).to.have.property("status", 200);
    });
  });

  it("Search and delete trigger email", () => {
    cy.visit('s/emails');
    emails.waitforPageLoad();
    cy.visit("/s/emails?search=" + testTriggerEmail);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
    emails.checkNoResultFoundMessage.should("contain", "No Results Found");
  });

  it("Delete newly added Trigger", () => {
    points.waitforPointTriggerPageLoad();
    cy.visit("/s/points/triggers?search=" + testTrigger);
    points.searchAndSelectFirstCheckBoxForTrigger.click();
    points.editOptionsForFirstSelectionForTrigger.click();
    points.deleteOption.click();
    points.confirmWindowDelete.click();
    points.checkNoResultFoundMessage.should("contain", "No Results Found");
  });
});
