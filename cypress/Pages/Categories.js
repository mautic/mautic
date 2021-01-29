"use strict";
class Categories {

    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Categories');
    }
  
    get categoryLink() {
        return  cy.get('a[href="/s/categories"]');
    }

    get categoryType() {
        return  cy.get('#category_form_bundle_chosen');
    }

    get searchCategoryType() {
        return  cy.get('#category_form_bundle_chosen>div>div>input');
    }

    get selectTheFirstSearch() {
        return  cy.get('#category_form_bundle_chosen>div>ul>li');
    }

    get titleCategory() {
        return  cy.get('#category_form_title');
    }

    get createNewCategory() {
        return  cy.get('a[href*="category/new"]');
    }

    get saveAndCloseChanges() {
        return  cy.get('button[class="btn btn-default btn-save btn-copy"]');
    }

    get applyChanges() {
        return  cy.get('button[class="btn btn-default btn-apply btn-copy"]');
    }

    get cancelChanges() {
        return  cy.get('button[class="btn btn-default btn-cancel btn-copy"]');
    }

    get selectParentButtonForDelete() {
        return  cy.get('#categoryTable>thead>tr>th>div>div>button>i');
    }

    waitTillCategoryPopUpLaunch(){
        cy.get('#MauticSharedModal-label').should('be.visible').should('contain', 'New Category');
    }

    get waiitTillCategoryCreation(){
        return   cy.get('#categoryTable>tbody>tr>td>div>a');
    }

    get checkNoResultFoundMessage() {
        return cy.get('#app-content>div>div>div>div>h4');
    }
}

const catagory = new Categories();
module.exports = catagory;

