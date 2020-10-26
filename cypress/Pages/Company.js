"use strict";
class Company {
    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Companies');
    }
    get addNewButton() {
        return cy.get("#toolbar > div.std-toolbar.btn-group > a > span > span")
        .first();
    }
    get enterAndSearchForTheCompany() {
        return cy.get('table[class="table table-hover table-striped table-bordered company-list"]>tbody>tr>td>div>a');
    }

    get companyName() {
        return  cy.get("#company_companyname");
    }

    get companyCity() {
        return  cy.get('#company_companycity');
    }

    get companyAddressOne() {
        return  cy.get('#company_companyaddress1');
    }

    get companyAddressTwo() {
        return  cy.get('#company_companyaddress2');
    }

    get companyZipCode() {
        return  cy.get('#company_companyzipcode');
    }

    get companyEmailAddress() {
        return  cy.get('#company_companyemail');
    }

    get companyPhoneNumber() {
        return  cy.get('#company_companyphone');
    }

    get companyWebsite() {
        return  cy.get('#company_companywebsite');
    }

    get saveButton() {
        return   cy.get("#company_buttons_save_toolbar");
    }

    get searchAndClickForFirstElement() {
        return   cy.get('#companyTable>tbody>tr>td>div>a');
    }
  
}
const company = new Company();
module.exports = company;
