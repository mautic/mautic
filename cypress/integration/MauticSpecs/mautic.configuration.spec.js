/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const configuration = require("../../Pages/Configuration");

context("Verify that user is able to update the configuration settings", () => {
  it("Update Email Settings", () => {
    cy.visit('s/config/edit');
    configuration.waitforPageLoad();
    configuration.clickOnEmailSettings.click({force: true})
    configuration.waitforEmailSettingPageLoad()
    configuration.selectFrquencyForEmail.clear().type('5')
    configuration.clickFrequencyEach.click()
    configuration.selectFrequencyForWeek.click()
    configuration.saveAndCloseEmailSetting.click({force: true})
    configuration.waitTillUserRedirectedToDashboard()
    configuration.closeAlert.click()
  })

  });


