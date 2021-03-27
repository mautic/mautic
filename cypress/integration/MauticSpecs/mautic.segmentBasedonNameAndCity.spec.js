/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const contact = require("../../Pages/Contacts");
const company = require("../../Pages/Company");
const segments = require("../../Pages/Segments");
const search = require("../../Pages/Search");

var contactFirstName1 = "Sherlock"; // Community Specific
var contactFirstName2 = "Poirot"; // Community Specific
var companyName = "Acquia"
var cronpath = Cypress.env('cron-path'); // Community Specific

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
    contact.firstName.type(contactFirstName1); // Community Specific
    contact.lastName.type("contact1");
    contact.leadEmail.type(contactFirstName1 + "contact1@mailtest.mautic.org"); // Community Specific
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
    contact.firstName.type(contactFirstName2); // Community Specific
    contact.lastName.type("contact2");
    contact.leadEmail.type(contactFirstName2 + "contact2@mailtest.mautic.org"); // Community Specific
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
    contact.firstName.type(contactFirstName1); // Community Specific
    contact.lastName.type("contact3");
    contact.leadEmail.type(contactFirstName1 + "contact3@mailtest.mautic.org"); // Community Specific
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
    contact.firstName.type(contactFirstName2); // Community Specific
    contact.lastName.type("contact4");
    contact.leadEmail.type(contactFirstName2 + "contact4@mailtest.mautic.org"); // Community Specific
    contact.leadCity.type("Pune");
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
      segments.filterValue.type(contactFirstName1, { force: true }); // Community Specific

      segments.filterDropDown.click();
      segments.filterSearchBox.type("City", { force: true });
      segments.filterCityField.click(); // Community Specific
      segments.waitTillSecondOperatorFilterGetsLoaded();
      segments.secondFilterProperties.type("Bidar", { force: true });

      segments.filterDropDown.click();
      segments.filterSearchBox.type("Primary company"); // Community Specific
      segments.filterField.contains('Primary company').click(); // Community Specific
      segments.waitTillThirdOperatorFilterGetsLoaded();
      segments.thirdFilterProperties.type("Acquia", { force: true });
      segments.saveAndCloseButton.click();
      segments.waitforSegmentCreation();
      cy.exec(cronpath + ' m:s:r'); //Community specific
    }
  );

  it("Verify that segmentMembershipWithNameCityAndCompany segment has two contacts only",
    () => {
        cy.visit("s/segments");
        segments.waitForPageLoad();
        cy.visit("/s/segments?search=segment");
        segments.checkContactsUnderSegment.should('contain',"View 2 Contacts"); // Community Specific
        segments.checkContactsUnderSegment.click();
        segments.checkDetailContactsUnderSegment
            .should("contain", contactFirstName1 +" contact1") // Community Specific
            .should("contain", contactFirstName1 +" contact3"); // Community Specific
    }
  );

  it("Search and delete segmentMembershipWithCustomField segment", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit("/s/segments?search=segment");
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  })

  it("Search and delete newly added contacts", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    cy.visit("/s/contacts?search=contact"); // Community Specific
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
