/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const segments = require("../../Pages/Segments");
const contact = require("../../Pages/Contacts");
const search =require("../../Pages/Search");

var cypressSegment = "CypressSegment";
var bidarCity = "bidarCitySegment";
var hydrabadCity = "hydrabadCitySegment";
var contactOneForBidar = "User1 Tester";
var contactTwoForBidar = "User2 Tester";
var contactOneForHydrbad = "User3 Tester";
var cronpath = Cypress.env('cron-path'); // Community Specific


context("Verify that user is able to create segment and test that contacts are getting added as per the selected filter", () => {

  beforeEach("Visit HomePage", () => {
    cy.visit("s/segments");
  });

  it("Add new contact for Bidar city", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type("User1");
    contact.lastName.type("Tester");
    contact.leadEmail.type("Cypress1@mailtest.mautic.org");
    contact.leadCity.type('Bidar')
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new contact for Bidar city", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type("User2");
    contact.lastName.type("Tester");
    contact.leadEmail.type("Cypress2@mailtest.mautic.org");
    contact.leadCity.type('Bidar')
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });

  it("Add new contact for Hydrabad city", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type("User3");
    contact.lastName.type("Tester");
    contact.leadEmail.type("Cypress3@mailtest.mautic.org");
    contact.leadCity.type('Hydrabad')
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  });


  it("Add new Segment", () => {
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    segments.segmentName.type(cypressSegment);
    segments.waitTillNewSegmentGetsOpen()
    segments.filterTab.click();
    segments.filterDropDown.click();
    segments.filterSearchBox.type("First");
    segments.filterField.click();
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterValue.type("Cypress");
    segments.saveAndCloseButton.click();
    segments.waitForPageLoad();

  });

  it("Add new segment for Bidar city", () => {
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    segments.waitTillNewSegmentGetsOpen()
    segments.segmentName.type(bidarCity);
    segments.filterTab.click();
    segments.filterDropDown.click();
    segments.filterSearchBox.type("City");
    segments.filterCityField.click();
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterValue.type("Bidar");
    segments.saveAndCloseButton.click();
    segments.waitForPageLoad();
  });

  it("Add new segment for Hydrabad city", () => {
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    segments.waitTillNewSegmentGetsOpen()
    segments.segmentName.type(hydrabadCity);
    segments.filterTab.click();
    segments.filterDropDown.click();
    segments.filterSearchBox.type("City");
    segments.filterCityField.click();
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterValue.type("Hydrabad");
    segments.saveAndCloseButton.click();
    segments.waitForPageLoad()
    cy.exec(cronpath + ' m:s:r'); //Community specific
  });

  it("Verify that Bidar city segment has two contacts only", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + bidarCity)
    segments.checkContactsUnderSegment.should('contain','View 2 Contacts')
    segments.checkContactsUnderSegment.click()
    segments.checkDetailContactsUnderSegment.should('contain',contactOneForBidar).should('contain',contactTwoForBidar);
  });

  it("Verify that hydrabad city segment has one contact only", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + hydrabadCity)
    segments.checkContactsUnderSegment.should('contain','View 1 Contact')
    segments.checkContactsUnderSegment.click()
    segments.checkDetailContactsUnderSegment.should('contain',contactOneForHydrbad);
  });

  it("Edit newly added segment", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + cypressSegment)
    segments.searchAndSelectSegment.contains(cypressSegment).click();
    segments.waitTillClickedSegmentGetsOpen();
    segments.editSegment.click();
    segments.filterTab.click();
    segments.filterDropDown.click();
    segments.filterSearchBox.type("Last name");
    segments.filterField.click();
    segments.leadListFilter.select("or").should('have.value', 'or'); // Community Specific
    segments.filterValue.type("Test");
    segments.saveAndCloseButton.click();
    segments.waitforSegmentUpdate();
  });

  it("Search and delete a contacts created for Bidar and Hydrabad City", () => {
    cy.visit('/s/contacts');
    contact.waitforPageLoad();
    cy.visit('/s/contacts?search=User');
    search.selectTheParentCheckBox.click();
    search.selectTheParentDropdown.click();
    search.deleteAllSelected.click();
    search.confirmDeleteButton.click();
    search.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  it("Search and delete cypress segment", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + cypressSegment)
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });

  it("Search and delete Bidar city segment", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + bidarCity)
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });

  it("Search and delete Hydrabad city segment", () => {
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=' + hydrabadCity)
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });

});
