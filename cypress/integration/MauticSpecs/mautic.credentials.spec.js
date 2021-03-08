/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const credentials = require("../../Pages/Credentials");
const search = require("../../Pages/Search");
var getHostUrl = Cypress.config().baseUrl
var appendUrl = "/oauth/v2/token"
var bearerToken;
var apiKey = "";
var apiSecretValue = "";
var contactsEndPoint = "/api/contacts"
var createContactEndPoint = "/api/contacts/new"
var count = 0
var getContactId = ""
var getCreatedContactId = ""

context("Verify that user is able to create credentials and update the contact fields using them", function() {

  it("Add new credential", function() {
    cy.visit("s/credentials");
    credentials.addNewButton.click();
    credentials.oAuth2ClientApiModeSelector.then(() => {
      cy.wait(1000);
      credentials.clientName.type("Test");
      credentials.clientRedirectUI.type("https://on.cypress.io");
      credentials.saveAndCloseButton.click();
    });
    credentials.waitforPageLoad();
    var key1 = "";
    var secret1 = "";
    credentials.apiKey.invoke("val").then((key) => {
      key1 = key;
      credentials.apiSecret.invoke("val").then((secret) => {
        secret1 = secret;
        var token = "";
        cy.request("POST", "/oauth/v2/token", {
          grant_type: "client_credentials",
          client_id: key1,
          client_secret: secret1,
        }).then((response) => {
          token = response.body.access_token;
          cy.request("GET", "/api/contacts?access_token=" + token);
        });
      });
    });
  })

  it("Get the generated API key and secret", function() {
    cy.visit("s/credentials");
    credentials.apiKey.invoke('val').then((text1)=>{
      apiKey = text1
    })

    credentials.apiSecret.invoke('val').then((text2)=>{
      apiSecretValue = text2
     })
  })

  it("Hit the Auth request and verify that bearer token gets generated", function() {
    cy.request({ 
     method:'POST',
     url: getHostUrl + appendUrl,
     body:
     {
      'grant_type': 'client_credentials',
      'client_id': apiKey,
      'client_secret': apiSecretValue
     },
     headers:{
       'Content-Type':'application/json',
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      bearerToken = response.body.access_token
    })
  })

  it("Hit the contacts endpoint and get the contact id", function() {
    cy.request({ 
     method:'GET',
     url: getHostUrl + contactsEndPoint,
     headers:{
       'Content-Type':'application/json',
       'Connection':'keep-alive',
       'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      Object.keys(response.body.contacts).forEach(function(key){
        if(count==0)
        {
        getContactId = response.body.contacts[key].id
        count++
        }
      })
    })
  })

  it("Hit GET Request selected contact endpoint and verify that Country field is empty", function() {
    cy.request({ 
     method:'GET',
     url: getHostUrl + contactsEndPoint + '/' + getContactId,
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      expect(response.body.contact).has.property('id',getContactId)
      expect(response.body.contact.fields.core.country).has.property('value',null)
    })
  })

  it("Hit PATCH request selected contact endpoint and update country field to India and verify that country field gets updated", function() {
    cy.request({ 
     method:'PATCH',
     url: getHostUrl + contactsEndPoint + '/' + getContactId + 'edit',
     body:
     {
      "country" : "India"
     },
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      expect(response.body.contact).has.property('id',getContactId)
      expect(response.body.contact.fields.core.country).has.property('value','India')
    })
  })

  it("Hit GET Request selected contact endpoint and verify that Country field is updated to India", function() {
    cy.request({ 
     method:'GET',
     url: getHostUrl + contactsEndPoint + '/' + getContactId,
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      expect(response.body.contact).has.property('id',getContactId)
      expect(response.body.contact.fields.core.country).has.property('value',"India")
    })
  })

  it("Hit the contact creation endpoint and create a new contact", function() {
    cy.request({ 
     method:'POST',
     url: getHostUrl + createContactEndPoint,
     headers:{
       'Content-Type':'application/json',
       'Connection':'keep-alive',
       'Authorization': 'Bearer ' + bearerToken
     },
     body:
     {
      "firstname":"Test2",
      "lastname":"Contact2",
      "email":"test2contact2@mailtest.mautic.com"
     }
    }).then(function(response){
      expect(response.body).to.not.be.null
      getCreatedContactId = response.body.contact.id
      expect(response).to.have.property('status',201)
    })
  })

  it("Hit GET Request and verify that contact is created", function() {
    cy.request({ 
     method:'GET',
     url: getHostUrl + contactsEndPoint + '/' + getCreatedContactId,
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      expect(response.body.contact).has.property('id',getCreatedContactId)
    })
  })

  it("Delete the created contact and verify that it gets deleted", function() {
    cy.request({ 
     method:'DELETE',
     url: getHostUrl + contactsEndPoint + '/' + getCreatedContactId + 'delete',
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',200)
      expect(response.body).to.not.be.null
      expect(response.body.contact).has.property('id',null)
    })
  })

  it("Hit GET Request and verify that contact got deleted", function() {
    cy.request({ 
     method:'GET',
     url: getHostUrl + contactsEndPoint + '/' + getCreatedContactId,
     failOnStatusCode: false,
     headers:{
      'Content-Type':'application/json',
      'Connection':'keep-alive',
      'Authorization': 'Bearer ' + bearerToken
     }
    }).then(function(response){
      expect(response).to.have.property('status',404)
    })
  })

  it("Search and Delete Credentials",function(){
    cy.visit("/s/credentials");
    search.selectCheckBoxForFirstItem.click({ force: true });
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  })

});
