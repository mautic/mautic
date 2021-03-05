"use strict";
class Credentials {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'API Credentials');
    }
    get addNewButton() {
        return cy.get('.std-toolbar > .btn');
    }
  
    get oAuth1ApiModeSelector() {

        return cy.get('#api_mode').select("OAuth 1")

    }

    get oAuth2ApiModeSelector() {

        return cy.get('#api_mode').select("OAuth 2")

    }

    get oAuth1ClientApiModeSelector() {

        return cy.get('#client_api_mode').select("OAuth 1.0")

    }

    get oAuth2ClientApiModeSelector() {

        return cy.get('#client_api_mode').select("OAuth 2",{force:true});

    }

    get clientName(){
        return cy.get('#client_name');
    }

    get clientRedirectUI(){
        return cy.get('#client_redirectUris');
    }

    get saveAndCloseButton(){
        return cy.get('#client_buttons_save_toolbar');
    }

    get apiKey(){
        return cy.get('table[class*="table-bordered client-list"]>tbody>tr>td>input').eq(0);
    }

    get apiSecret(){
        return cy.get('table[class*="table-bordered client-list"]>tbody>tr>td>input').eq(1);
    }


   
}
const credential = new Credentials();
module.exports = credential;
