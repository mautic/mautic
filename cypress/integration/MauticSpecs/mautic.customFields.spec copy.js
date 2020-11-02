/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const settings = require("../../Pages/Settings");
const customFields = require("../../Pages/CustomFields");

context("Custom Fields", () => {
   
  it("add new Booleean custom field for Company", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click();
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type("Booleean custom field for Company");
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Company",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Boolean",{force: true});
    customFields.SaveAndCloseButton.click();
  })

  it("add new Booleean custom field for Contact", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click();
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type("Booleean custom field for Contact");
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Boolean",{force: true});
    customFields.SaveAndCloseButton.click();

  it("add new Text custom field for Company", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click();
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type("Text custom field for Contact");
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Company",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Text",{force: true});
    customFields.SaveAndCloseButton.click();
  })

  it("add new Text custom field for Contact", () => {
    settings.settingsMenuButton.click();
    settings.customFieldSection.click();
    customFields.waitforPageLoad();
    customFields.addNewButton.click();
    customFields.fieldLabel.type("Text custom field for Contact");
    customFields.ObjectSelectionDropDown.click();
    customFields.ObjectSelector.select("Contact",{force: true});
    customFields.DataTypeSelectionDropDown.click();
    customFields.DataTypeSelector.select("Text",{force: true});
    customFields.SaveAndCloseButton.click();
  })


  });

});
