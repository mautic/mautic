"use strict";
class Search {
    get searchBox() {
        return cy.get('#list-search');
    }

    get searchForm() {
        return cy.get('input[id="form-search"]');
    }

    get searchClear() {
        return cy.get('#btn-filter');
    }

    get selectCheckBoxForFirstItem() {
        return cy.get('.list-checkbox').eq(0);
    }

    get OptionsDropdownForFirstItem() {
        return cy.get('button[class="btn btn-default btn-sm dropdown-toggle btn-nospin"]').eq(0);
    }

    get deleteButtonForFirstItem() {
        return cy.get('a[href*="delete"]');
    }

    get confirmDeleteButton(){
        return cy.get('button[class="btn btn-danger"]');
    }

    get selectAndClickFirstItemsOption(){
        return cy.get('#stageTable>tbody>tr>td>div>div>button');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

    get saveAndCloseButton() {
        return cy.get('#dwc_buttons_save_toolbar');
    }

    get applyButton() {
        return cy.get('#dwc_buttons_apply_toolbar');
    }
  
}
const search = new Search();
module.exports = search;
