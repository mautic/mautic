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
var testContact = "testcontact";
import "./commands";

Cypress.Cookies.defaults({
  preserve: [Cypress.env("instanceId"),'_ga','_gid','_gat','mautic_referer_id','mtc_id','mtc_sid','mautic_device_id','device_id','sid','id','success','__Secure-3PAPISID','SAPISID','APISID','__Secure-3PSID','SID','SSID','HSID','NID','1P_JAR','ANID','SIDCC','OTZ'],
}); // Required to persist cookies


 before("Perform login", () => {
  cy.visit("/");
  cy.location().then((loc) => {
    console.log(loc)
    if(loc.pathname.includes('login')){
      cy.log("Logging in");
      cy.login(Cypress.env("userName"), Cypress.env("password"));
      cy.log('Login successful')
    }
  })
});

Cypress.on("uncaught:exception", (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test
  return false;
});

// Alternatively you can use CommonJS syntax:
// require('./commands')
