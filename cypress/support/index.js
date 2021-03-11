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
  // Preserve all cookies, since Mautic's auth cookie has a different, unique name every time.
  preserve: /.*/
});


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
