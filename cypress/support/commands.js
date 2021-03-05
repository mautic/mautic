// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
  const cypress_file_upload = require('cypress-file-upload');

Cypress.Commands.add('upload_file', (fileName, fileType = ' ', selector) => {
    cy.get(selector).then(subject => {
      cy.fixture(fileName, 'base64')
        .then(Cypress.Blob.base64StringToBlob)
        .then(blob => {
          const el = subject[0]
          const testFile = new File([blob], fileName, { type: fileType })
          const dataTransfer = new DataTransfer()
          dataTransfer.items.add(testFile)
          el.files = dataTransfer.files
        })
    })
  })

Cypress.Commands.add("login", (email, password) => {
  if (cy.get('#username').should('exist'))
  {
  cy.get('#username').type(email)

  cy.get('#password').type(password,{ log: false })
  cy.get('#main > div.container > div:nth-child(1) > div > div > div > form > button').click(); 
  }
 })

 Cypress.Commands.add("convertToMauticDateFormat", (Date) => {
  var d = new Date(date),
      month = '' + (d.getMonth() + 1),
      day = '' + d.getDate(),
      year = d.getFullYear();

  if (month.length < 2)
      month = '0' + month;
  if (day.length < 2)
      day = '0' + day;

  return [year, month, day].join('-');
 })


//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
