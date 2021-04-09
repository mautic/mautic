/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const configuration = require("../../Pages/Configuration");

context("Verify that user is able to update the configuration settings", () => {
  it("Update Email Settings", () => {
    configuration.openSettings.click(); //Community specific
    configuration.goToConfig.click(); //Community specific
    cy.wait(60000);
    configuration.waitforPageLoad();
    configuration.clickOnEmailSettings.click({force: true});
    configuration.waitforEmailSettingPageLoad();
    configuration.selectFrequencyForEmail.clear().type('5');
    configuration.clickFrequencyEach.click();
    configuration.selectFrequencyForWeek.click();
    configuration.saveAndCloseEmailSetting.click({force: true});
    configuration.waitTillUserRedirectedToDashboard();
    configuration.closeAlert.click();
  })

  });


