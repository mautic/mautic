/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const contact = require("../../Pages/Contacts");
const company = require("../../Pages/Company");
const segments = require("../../Pages/Segments");
const search = require("../../Pages/Search");

var contactFirstName = "Segment_Test";
var companyName = "Acquia"
context("Verify segment membership based on Name ,City and Company", () => {

  it("Add new Company", () => {
    cy.visit("s/companies");
    company.waitforPageLoad();
    company.addNewButton.click({ force: true });
    company.companyName.type(companyName);
    company.saveButton.click();
    company.alertMessage.should("contain", "Acquia has been created!");
  });

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact1");
    contact.leadEmail.type(contactFirstName + "contact1@mailtest.mautic.com");
    contact.leadCity.type("Bidar");
    contact.companySearch.type("Acquia");
    contact.companySelector.first().click();
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact2");
    contact.leadEmail.type(contactFirstName + "contact2@mailtest.mautic.com");
    contact.leadCity.type("Bidar");
    contact.companySearch.type("Acquia");
    contact.companySelector.first().click();
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact3");
    contact.leadEmail.type(contactFirstName + "contact3@mailtest.mautic.com");
    contact.leadCity.type("Bidar");
    contact.companySearch.type("Acquia");
    contact.companySelector.first().click();
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact4");
    contact.leadEmail.type(contactFirstName + "contact4@mailtest.mautic.com");
    contact.leadCity.type("Bidar");
    contact.companySearch.type("Acquia");
    contact.companySelector.first().click();
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new segment segmentMembershipWithNameCityAndCompany to test segment membership",
    () => {
      cy.visit("s/segments");
      segments.waitForPageLoad();
      segments.addNewButton.click({ force: true });
      segments.segmentName.type("segmentBasedOnNameCityAndCompany");
      segments.waitTillNewSegmentGetsOpen();
      segments.filterTab.click();

      segments.filterDropDown.click();
      segments.filterSearchBox.type("First");
      segments.filterField.click();
      segments.waitTillFilterOptionGetsLoaded();
      segments.filterOperator.select("contains");
      cy.wait(1000); // Added wait for page rendering
      segments.filterValue.type(contactFirstName, { force: true });

      segments.filterDropDown.click();
      segments.filterSearchBox.type("City", { force: true });
      segments.filterField.click({ force: true });
      segments.waitTillSecondOperatorFilterGetsLoaded();
      segments.secondFilterOperator.select("contains");
      cy.wait(1000); // Added wait for page rendering
      segments.secondFilterProperties.type("Bidar", { force: true });

      segments.filterDropDown.click();
      segments.filterSearchBox.type("Company");
      segments.filterField.click({ force: true });
      segments.waitTillThirdOperatorFilterGetsLoaded();
      segments.thirdFilterOperator.select("contains");
      cy.wait(1000); // Added wait for page rendering
      segments.thirdFilterProperties.type("Acquia", { force: true });

      segments.saveAndCloseButton.click();
      segments.waitforSegmentCreation();
      cy.wait(3000); // Added wait for segment building
    }
  );

  it("Verify that segmentMembershipWithNameCityAndCompany segment has two contacts only",
    () => {
      cy.visit("s/segments");
      segments.waitForPageLoad();
      cy.visit("/s/segments?search=segment");
      segments.checkContactsUnderSegment.should("contain", "View 4 Contacts");
      segments.checkContactsUnderSegment.click();
      segments.checkDetailContactsUnderSegment
        .should("contain", "Test contact1")
        .should("contain", "Test contact2");
    }
  )

  it("Search and delete segmentMembershipWithCustomField segment", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit("/s/segments?search=segment");
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  })

  it("Search and delete newly added contact", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    cy.visit("/s/contacts?search=" + contactFirstName);
    contact.waitTillSearchResultGetsDisplayed();
    search.selectParentCheckBox.click({ force: true });
    search.selectParentsOptionsDropdown.click();
    search.selectBatchdeleteButton.click();
    search.confirmDeleteButton.click();
  })

  it("Search and delete newly added company", () => {
    cy.visit("s/companies");
    company.waitforPageLoad();
    cy.visit("/s/companies?search=" + companyName);
    company.waitTillSearchResultGetsDisplayed();
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  })

})
