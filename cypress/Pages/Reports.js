"use strict";
class Reports {
    
    waitforPageLoad(){
        return cy.get('h3.pull-left').should('contain', 'Reports');
    }

    get createNewReport(){
        return cy.get("a[href*='reports/new']");
    }

    get editeport(){
        return cy.get('a[href*="reports/edit"]');
    }

    waitforNewReportsPage(){
        return cy.get('#report_name').should('be.visible');
    }

    get reportName(){
        return cy.get('#report_name');
    }

    get ownerName(){
        return cy.get('#report_createdBy_chosen');
    }

    get chooseOwnersName(){
        return cy.get('#report_createdBy_chosen>div>ul>li').eq(1);
    }

    get dataTab(){
        return cy.get('a[href*="#data"]');
    }

    get columnTextBox(){
        return cy.get('#ms-report_columns>div[class="ms-selectable"]>input');
    }

    get selectColumnFromSearch(){
        return cy.get('#ms-report_columns>div>ul>li>span');
    }

    get saveAndClose(){
        return  cy.get('#report_buttons_save_toolbar');
    }

    waitforReportCreation(){
        return cy.get('span[class="tt-u label label-success"]').should('be.visible');
    }

    waitTillSearchItemGetsVisible(){
        return cy.get('#reportTable>tbody>tr>td>div>a').should('be.visible');
    }

    waitTillCreatedReportOpen(){
        return cy.get('a[href*="reports/edit"]').should('be.visible');
    }

    waitTillSelectedItemGetsAdded(){
        return cy.get('#ms-report_columns>div[class="ms-selection ui-sortable"]>ul[class="ms-list"]>li[class="ms-elem-selection ui-sortable-handle ms-selected"]').should('be.visible');
    }

    get clickOnFirstelementSearched(){
        return cy.get('#reportTable>tbody>tr>td>div>a');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

}
const reports = new Reports();
module.exports = reports;
