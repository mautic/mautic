"use strict";
class Roles {

    waitForPageLoad() {
            cy.get('h3.pull-left').should('contain', 'Roles'); 
    }
    get addNewRoleButton() {
        return cy.get('a[href="/s/roles/new"]');
    }

    get searchRole() {
        return  cy.get('#list-search');
    }

    get clickOnFirstRole() {
        return  cy.get('table[class="table table-hover table-striped table-bordered role-list"]>tbody>tr>td>a');
    }

    get roleNameTextBox() {
        return  cy.get('#role_name');
    }

    get permissionTab() {
        return  cy.get('a[href*="#permissions-container"]');
    }

    get roleNameDescriptionTextBox() {
        return  cy.get('#div[class="fr-element fr-view"]');
    }

    get fullSystemAccess_Yes() {
        return  cy.get('label[class="btn btn-default  btn-yes"]>span');
    }

    get fullSystemAccess_No() {
        return  cy.get('label[class="btn btn-default  active btn-no btn-danger"]>span');
    }

    get apiPermissionTab() {
        return  cy.get('a[href*="#apiPermissionTab"]');
    }

    get apiPermissionTab() {
        return  cy.get('a[href*="#apiPermissionTab"]');
    }

    get assetPermissionTab() {
        return  cy.get('a[href*="#assetPermissionTab"]');
    }

    get campaignPermissionTab() {
        return  cy.get('a[href*="#campaignPermissionTab"]');
    }

    get categoryPermissionTab() {
        return  cy.get('a[href*="#categoryPermissionTab"]');
    }

    get contactPermissionTab() {
        return  cy.get('a[href*="#leadPermissionTab"]');
    }

    get channelPermissionTab() {
        return  cy.get('a[href*="#channelPermissionTab"]');
    }

    get channelPermissionTab() {
        return  cy.get('a[href*="#channelPermissionTab"]');
    }

    get apiAccessGrantedCheckBox() {
        return  cy.get('div[class="pt-md pr-md pl-md pb-md"]>div>div>div>div>label>input').eq(0);
    }

    get contacts_Access_ViewOwn() {
        return  cy.get('div[class="pt-md pr-md pl-md pb-md"]>div>div>div>div>label>input').eq(60);
    }

    get contacts_Access_ViewOthers() {
        return  cy.get('div[class="pt-md pr-md pl-md pb-md"]>div>div>div>div>label>input').eq(61);
    }

    get saveAndCloseTab() {
        return  cy.get('#role_buttons_save_toolbar');
    }

    waitforRoleCreation(){
        cy.get('#roleTable>tbody>tr>td>a[href*="roles"]').should('be.visible');
    }
}
const roles = new Roles();
module.exports = roles;
