"use strict";
class Search {
    get searchBox() {
        return cy.get('#list-search');
    }

    get searchForm() {
        return cy.get('input[id="form-search"]');
    }

    get selectCheckBoxForFirstItem() {
        return cy.get('.list-checkbox').eq(0);
    }

    get selectParentCheckBox() {
        return cy.get('#customcheckbox-one0');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }

    get selectTheParentCheckBox() {
        return cy.get('#customcheckbox-one0');
    }

    get selectTheParentDropdown() {
        return cy.get('#leadTable>thead>tr>th>div>div>button');
    }

    get deleteAllSelected() {
        return cy.get('a[href*="batchDelete"]');
    }

    get OptionsDropdownForFirstItem() {
        return cy.get('button[class="btn btn-default btn-sm dropdown-toggle btn-nospin"]').eq(0);
    }

    get selectParentsOptionsDropdown() {
        return cy.get('div[class="input-group input-group-sm"]>div');
    }

    get deleteButtonForFirstItem() {
        return   cy.get('a[href*="delete"]').eq(0);
    }

    get selectBatchdeleteButton() {
        return   cy.get('a[href*="batchDelete"]');
    }

    get confirmDeleteButton(){
        return cy.get('button[class="btn btn-danger"]')
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
