/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });

const customFields = require("../../Pages/CustomFields");
const contact = require("../../Pages/Contacts");
const search = require("../../Pages/Search");
const segments = require("../../Pages/Segments");
const campaigns = require("../../Pages/Campaigns");

var dateField1 = "dateField 1"
var dateField2 = "dateField 2"
var dateField3 = "dateField 3" 
var updatedAbsoluteDate = "2021-01-06"
var contactFirstName = "test1"
var date1 = "2021-01-04"
var date2 = "2021-02-04"
var date3 = "2021-03-04"
var segmentMembershipWithCustomField = " segment with date field"
var campaignMembershipWithUpdateContactAbsoluteDate = "campaignMembershipWithUpdateContactAbsoluteDate"
var campaignMembershipWithUpdateContactRelativeDate = "campaignMembershipWithUpdateContactRelativeDate"

var myCurrentDate=new Date();
var updatedRelativeDate=new Date(myCurrentDate);

function formatDate(date) {
  var d = new Date(date),
      month = '' + (d.getMonth() + 1),
      day = '' + d.getDate(),
      year = d.getFullYear();

  if (month.length < 2) 
      month = '0' + month;
  if (day.length < 2) 
      day = '0' + day;

  return [year, month, day].join('-');
}

context("Verify campaign membership with update contact action & updating dates (Relative and absolute) with date custom fields", () => {
   
  beforeEach("Visit HomePage", () => {
    cy.visit("s/contacts/fields");
  });

  it("Add" + " "+dateField1+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    cy.wait(4000); //Community specific
    customFields.fieldLabel.type(dateField1, { force: true }); //Community specific
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Date",{force: true});
    customFields.SaveAndCloseButton.click();
    customFields.waitforPageLoad();
  });

  it("Add" + " "+dateField2+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type(dateField2);
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Date",{force: true});
    customFields.SaveAndCloseButton.click();
    customFields.waitforPageLoad();
  });

  it("Add" + " "+dateField3+ " "+ "custom field for contact", () => {
    customFields.waitforPageLoad()
    customFields.addNewButton.click()
    customFields.fieldLabel.type(dateField3)
    customFields.ObjectSelectionDropDown.click()
    customFields.ObjectSelector.select("Contact",{force: true})
    customFields.DataTypeSelectionDropDown.click()
    customFields.DataTypeSelector.select("Date",{force: true})
    customFields.SaveAndCloseButton.click()
    customFields.waitforPageLoad()
    cy.wait(3000) // Added wait to get custom field published
  });

  it("Add new contact for segment membership", () => {
    cy.visit("s/contacts");
    contact.waitforPageLoad();
    contact.addNewButton.click({ force: true });
    contact.title.type("Mr");
    contact.firstName.type(contactFirstName);
    contact.lastName.type("contact1");
    contact.leadEmail.type(contactFirstName + "contact1@mailtest.mautic.com");
    contact.dateFieldOne.type(date1)
    contact.dateFieldSecond.type(date2)
    contact.dateFieldThird.type(date3)
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
    contact.dateFieldOne.type(date1)
    contact.dateFieldSecond.type(date2)
    contact.dateFieldThird.type(date3)
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
    contact.dateFieldOne.type(date1)
    contact.dateFieldSecond.type(date2)
    contact.dateFieldThird.type(date3)
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
    contact.dateFieldOne.type(date1)
    contact.dateFieldSecond.type(date2)
    contact.dateFieldThird.type(date3)
    contact.SaveButton.click();
    contact.closeButton.click({ force: true });
    contact.waitForContactCreation()
  });

  it("Add new segment " + segmentMembershipWithCustomField +" to test segment membership", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    segments.addNewButton.click({ force: true });
    segments.segmentName.type(segmentMembershipWithCustomField);
    segments.waitTillNewSegmentGetsOpen()
    segments.filterTab.click();

    segments.filterDropDown.click()
    segments.filterSearchBox.type("First")
    segments.filterField.click()
    segments.waitTillFilterOptionGetsLoaded()
    segments.filterOperator.select('contains')
    segments.filterValue.type(contactFirstName, { force: true })
    segments.saveAndCloseButton.click()
    segments.waitforSegmentCreation()
    cy.exec('ddev exec bin/console m:s:r'); //Community specific
  });

  it("Add new campaign " + campaignMembershipWithUpdateContactAbsoluteDate, () => {
    cy.visit("s/campaigns");
    campaigns.waitforPageLoad();
    search.searchBox.should('exist');
    campaigns.addNewButton.click();
    campaigns.campaignName.type(campaignMembershipWithUpdateContactAbsoluteDate);
    campaigns.launchCampaignBuilderButton.click({ force: true });
    campaigns.sourceSelector.select("Contact segments", { force: true });
    campaigns.segmentSelectorButton.type(segmentMembershipWithCustomField)
    cy.wait(3000); //Community specific
    campaigns.segmentSelector.click();
    campaigns.addSourceCamapignButton.click({ force: true });
    campaigns.addStepButtonBottom.click({ force: true });
    campaigns.actionSelector.click();
    campaigns.listOfActions.select("Update contact", { force: true });
    campaigns.nameOfActionPopUpVisibility()
    campaigns.nameOfAction.type('Update contact fields')
    campaigns.updateDateField1.type(updatedAbsoluteDate)
    campaigns.addButtonForAction.click()
    campaigns.closeBuilderButton.click();
    campaigns.publishToggleYes.click();
    campaigns.saveAndCloseButton.click();
    campaigns.closeSummaryPageButton.click();
    search.searchBox.clear();
    search.searchBox.type(campaignMembershipWithUpdateContactAbsoluteDate);
    search.selectCheckBoxForFirstItem.should('exist');
    cy.exec('ddev exec bin/console m:c:r'); //Community specific
    cy.exec('ddev exec bin/console m:c:t'); //Community specific
  });

  it("Verify that in "+ segmentMembershipWithCustomField +" segment contacts date field 1 got updated Only", () => {
    cy.visit("s/segments");
    segments.waitForPageLoad();
    cy.visit("/s/segments?search=segment"); //Community specific
    segments.getSegment.click()
    cy.wait(1000); //Community specific
    segments.waitforSegmentPageLoad();
    segments.getContactInSegment.click();
    cy.get('.mt-5 > :nth-child(1)').should('be.visible'); //Community specific
    contact.getContactDetails.click();
    cy.wait(1000); //Community specific
    contact.contactDetailsTab_DateField1Value.should('contain',updatedAbsoluteDate);
    contact.contactDetailsTab_DateField2Value.should('contain',date2);
    contact.contactDetailsTab_DateField3Value.should('contain',date3);
    contact.contactDetailsTab_LastDateActive.should('contain',' ');
  });

  it("Add new campaign " + campaignMembershipWithUpdateContactRelativeDate, () => {
    cy.visit("s/campaigns");
    campaigns.waitforPageLoad();
    search.searchBox.should('exist');
    campaigns.addNewButton.click();
    campaigns.campaignName.type(campaignMembershipWithUpdateContactRelativeDate);
    campaigns.launchCampaignBuilderButton.click({ force: true });
    campaigns.sourceSelector.select("Contact segments", { force: true });
    campaigns.segmentSelectorButton.click();
    campaigns.segmentSelectorButton.type(segmentMembershipWithCustomField)
    campaigns.segmentSelector.click();
    campaigns.addSourceCamapignButton.click({ force: true });
    campaigns.addStepButtonBottom.click({ force: true });
    campaigns.actionSelector.click();
    campaigns.listOfActions.select("Update contact", { force: true });
    campaigns.nameOfActionPopUpVisibility()
    campaigns.nameOfAction.type('Update contact fields')
    campaigns.updateDateField2.type('+2 Days')
    campaigns.updateDateField3.type('-3 Days')
    campaigns.addButtonForAction.click()
    campaigns.closeBuilderButton.click();
    campaigns.publishToggleYes.click();
    campaigns.saveAndCloseButton.click();
    campaigns.closeSummaryPageButton.click();
    search.searchBox.clear();
    search.searchBox.type(campaignMembershipWithUpdateContactRelativeDate);
    search.selectCheckBoxForFirstItem.should('exist');
    cy.exec('ddev exec bin/console m:c:r'); //Community specific
    cy.exec('ddev exec bin/console m:c:t'); //Community specific
  });

  it("Verify that in "+ segmentMembershipWithCustomField +" segment contacts date field 2 and 3 got updated Only", () => {
    cy.visit("/s/segments?search=segment"); //Community specific
    segments.waitForPageLoad();
    segments.getSegment.click()
    cy.wait(1000); //Community specific
    segments.getContactInSegment.click(); //Community specific
    cy.get('.mt-5 > :nth-child(1)').should('be.visible'); //Community specific
    contact.getContactDetails.click();
    cy.wait(1000), //Community specific
    contact.contactDetailsTab_DateField1Value.should('contain',updatedAbsoluteDate);
    contact.contactDetailsTab_DateField2Value.should('contain',formatDate(updatedRelativeDate.setDate(updatedRelativeDate.getDate() + 2)));
    updatedRelativeDate = myCurrentDate;
    contact.contactDetailsTab_DateField3Value.should('contain',formatDate(updatedRelativeDate.setDate(updatedRelativeDate.getDate() - 3)));
    contact.contactDetailsTab_LastDateActive.should('contain',' ');
  })

  it("Search and delete "+ campaignMembershipWithUpdateContactAbsoluteDate + " Campaign", () => {
    cy.visit("s/campaigns");
    campaigns.waitforPageLoad();
    cy.visit('/s/campaigns?search='+ campaignMembershipWithUpdateContactAbsoluteDate)
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click({ force: true });
    search.confirmDeleteButton.click();
  });

  it("Search and delete "+ campaignMembershipWithUpdateContactRelativeDate + " Campaign", () => {
    cy.visit("s/campaigns");
    campaigns.waitforPageLoad();
    cy.visit('/s/campaigns?search='+ campaignMembershipWithUpdateContactRelativeDate)
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click({ force: true });
    search.confirmDeleteButton.click();
  });

  it("Search and delete"+ segmentMembershipWithCustomField + "segment", () => {
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
    cy.visit('/s/contacts/fields?search=dateField');
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
    search.selectBatchdeleteButton.click();
    search.confirmDeleteButton.click();
  })

  })
