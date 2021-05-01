/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const customFields = require("../../Pages/CustomFields");
const contact = require("../../Pages/Contacts");
const search = require("../../Pages/Search");
const segments = require("../../Pages/Segments");

var numericField1 = "numericField 1"
var numericField2 = "numericField 2"
var booleanCustomField = "Boolean Custom Field"
var contactFirstName = "test1"
var segmentMembershipWithCustomField1 = " segment with numeric custom field and boolean 1"
var segmentMembershipWithCustomField2 = " segment with numeric custom field and boolean 2"
var cronpath = Cypress.env('cron-path'); // Community Specific

context("Verify segment membership tests with numeric custom field", () => {
   
  beforeEach("Visit HomePage", () => {
    cy.visit("s/contacts/fields");
  });

  it("Add" + " "+numericField1+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(numericField1);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Number",{force: true});
    customFields.SaveAndCloseButton.click();
    customFields.waitforPageLoad();
  })

  it("Add" + " "+numericField2+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(numericField2);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Number",{force: true});
    customFields.SaveAndCloseButton.click();
    customFields.waitforPageLoad();
  })

  it("Add" + " "+booleanCustomField+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(booleanCustomField);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Boolean",{force: true});
    customFields.SaveAndCloseButton.click();
    customFields.waitforPageLoad();
  })

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact1");
    contact.leadEmail.type(contactFirstName + "contact1@mailtest.mautic.org");
    contact.numericFieldOne.type('-1')
    contact.numericFieldSecond.type('5')
    contact.booleanCustomField_Yes.click()
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  })

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact2");
    contact.leadEmail.type(contactFirstName + "contact2@mailtest.mautic.org");
    contact.numericFieldOne.type('0')
    contact.numericFieldSecond.type('4')
    contact.booleanCustomField_No.click()
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  })

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact3");
    contact.leadEmail.type(contactFirstName + "contact3@mailtest.mautic.org");
    contact.numericFieldOne.type('2')
    contact.numericFieldSecond.type('1')
    contact.booleanCustomField_Yes.click()
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  })

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact4");
    contact.leadEmail.type(contactFirstName + "contact4@mailtest.mautic.org");
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation();
  })

  it("Add new segment " + segmentMembershipWithCustomField1 +" to test segment membership", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad()
    segments.addNewButton.click({ force: true })
    segments.segmentName.type(segmentMembershipWithCustomField1)
    segments.waitTillNewSegmentGetsOpen()
    segments.filterTab.click();

    segments.filterDropDown.click();
    segments.filterSearchBox.type("First");
    segments.filterField.click();
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterOperator.select('contains').should('have.value', 'contains'); //Community specific
    cy.wait(1000) // Added wait for page rendering
    segments.filterValue.type(contactFirstName, { force: true });

    segments.filterDropDown.click();
    segments.filterSearchBox.type(numericField1,{ force: true });
    segments.filterField.click({ force: true });
    segments.waitTillSecondOperatorFilterGetsLoaded()
    segments.secondFilterOperator.select('greater than').should('have.value', 'gt'); // Community Specific
    cy.wait(1000) // Added wait for page rendering
    segments.secondFilterProperties.type('0',{ force: true })

    segments.filterDropDown.click();
    segments.filterSearchBox.type(numericField2);
    segments.filterField.click({ force: true });
    segments.waitTillThirdOperatorFilterGetsLoaded()
    segments.thirdFilterOperator.select('greater than').should('have.value', 'gt'); // Community Specific
    cy.wait(1000) // Added wait for page rendering
    segments.thirdFilterProperties.type('0',{ force: true })

    segments.filterDropDown.click();
    segments.filterSearchBox.type(booleanCustomField);
    segments.filterField.click({ force: true });
    segments.waitTillFourthOperatorFilterGetsLoaded()
    segments.clickOnFourthFilterProperties.click({ force: true })
    segments.typeFourthFilterInput.type('Yes')
    segments.selectFourthTypedInput.click()

    segments.saveAndCloseButton.click()
    segments.waitforSegmentCreation()
    cy.exec(cronpath + ' m:s:r'); //Community specific
  })

  it("Verify that"+ segmentMembershipWithCustomField1 +"segment has two contacts only", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=segment')
    segments.checkContactsUnderSegment.should('contain','View 1 Contact'); //Community specific
    segments.checkContactsUnderSegment.click(); //Community specific
    segments.checkDetailContactsUnderSegment.should('contain',"test1 contact3");
  })

  it("Search and delete"+ segmentMembershipWithCustomField1 + "segment", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=segment')
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });

  it("Add new segment "+ segmentMembershipWithCustomField2+ " to test segment membership", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    segments.segmentName.type(segmentMembershipWithCustomField2);
    segments.waitTillNewSegmentGetsOpen()
    segments.filterTab.click();

    segments.filterDropDown.click();
    segments.filterSearchBox.type("First");
    segments.filterField.click();
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterOperator.select('contains').should('have.value', 'contains'); // Community Specific
    cy.wait(1000) // Added wait for page rendering
    segments.filterValue.type(contactFirstName, { force: true });

    segments.filterDropDown.click();
    segments.filterSearchBox.type(numericField1,{ force: true });
    segments.filterField.click({ force: true });
    segments.waitTillSecondOperatorFilterGetsLoaded();
    segments.secondFilterOperator.select('greater than').should('have.value', 'gt'); // Community Specific
    cy.wait(1000) // Added wait for page rendering
    segments.secondFilterProperties.type('1',{ force: true })

    segments.filterDropDown.click();
    segments.filterSearchBox.type(numericField2);
    segments.filterField.click({ force: true });
    segments.waitTillThirdOperatorFilterGetsLoaded()
    segments.thirdFilterOperator.select('equals')
    cy.wait(1000) // Added wait for page rendering
    segments.thirdFilterProperties.type('1',{ force: true })

    segments.saveAndCloseButton.click()
    segments.waitforSegmentCreation()
    cy.exec(cronpath + ' m:s:r'); //Community specific
  })

  it("Verify that"+ segmentMembershipWithCustomField2 +"segment has only one contact only", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=segment')
    segments.checkContactsUnderSegment.should('contain','View 1 Contact'); //Community specific
    segments.checkContactsUnderSegment.click(); //Community specific
    segments.checkDetailContactsUnderSegment.should('contain',"test1 contact3"); //Community specific
  })

  it("Search and delete"+ segmentMembershipWithCustomField2 + "segment", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit('/s/segments?search=segment')
    segments.firstCheckbox.click();
    segments.firstDropDown.click();
    segments.deleteOption.click();
    segments.deleteConfirmation.click();
  });

  it("Delete the created custom fields", () => {
    customFields.waitforPageLoad();
    cy.visit('/s/contacts/fields?search=numericField');
    customFields.selectAllCustomField.click();
    customFields.clickOnDropdownToDelete.click();
    customFields.deleteSelectedCustomField.click();
    customFields.waitTillConfirmationWindowGetsLoaded();
    customFields.confirmationWindowForDelete.click();
    customFields.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  it("Delete the created custom fields", () => {
    customFields.waitforPageLoad();
    cy.visit('/s/contacts/fields?search=Boolean');
    customFields.selectAllCustomField.click();
    customFields.clickOnDropdownToDelete.click();
    customFields.deleteSelectedCustomField.click();
    customFields.waitTillConfirmationWindowGetsLoaded();
    customFields.confirmationWindowForDelete.click();
    customFields.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  it("Search and delete newly added contact", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    cy.visit('/s/contacts?search=' + contactFirstName);
    contact.waitTillSearchResultGetsDisplayed();
    search.selectParentCheckBox.click({ force: true });
    search.selectParentsOptionsDropdown.click();
    search.selectBatchdeleteButton.click({force: true});
    search.confirmDeleteButton.click();
  });

  });

