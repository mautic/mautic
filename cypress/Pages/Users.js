"use strict";
class Users {

    waitForPageLoad() {
            cy.get('h3.pull-left').should('contain', 'Users');
            cy.wait(1000);
    }
    get clickOnUsersTab() {
        return cy.get('a[href="/s/users"]');
    }

    get clickOnCreateNewUserTab() {
        return cy.get('a[href*="users/new"]');
    }

    get User_firstName() {
        return cy.get('#user_firstName');
    }

    get User_lastName() {
        return cy.get('#user_lastName');
    }

    get User_Role() {
        return cy.get('#user_role_chosen');
    }

    get searchForRole() {
        return cy.get('#user_role_chosen>div>div>input');
    }

    get selectSearchedRole() {
        return cy.get('#user_role_chosen>div>ul>li');
    }

    get userName() {
        return cy.get('#user_username');
    }

    get userEmail() {
        return cy.get('#user_email');
    }

    get userPassword() {
        return cy.get('#user_plainPassword_password');
    }

    get userConfirmPassword() {
        return cy.get('#user_plainPassword_confirm');
    }

    get saveUser() {
        return cy.get('#user_buttons_save_toolbar');
    }

    waitTillNewUserCreationPageGetsLoaded() {
        cy.get('h3.pull-left').should('contain', 'Users - New User'); 
    }

    waitTillUserGetsCreated(){
        cy.get('#userTable>tbody>tr>td>div>a').should('be.visible');
    }
    
    get clickOnUserName() {
        return cy.get('span[class*="ml-xs hidden"]');
    }

    get clickOnLogoutButton() {
        return cy.get('a[href*="logout"]');
    }

    get selectParentCheckBox() {
        return cy.get('#customcheckbox-one0');
    }

    get selectParentDropdown() {
        return cy.get('#userTable>thead>tr>th>div>div>button>i');
    }

    get deleteUsersBatch() {
        return cy.get('a[href*="Delete"]');
    }

    get confirmDeleteButton() {
        return cy.get('button[class="btn btn-danger"]');
    }

    waitTillLoginPageDisplayed(){
        cy.get('#username').should('be.visible');
    }

    waitTillDashboardGetsLoaded() {
        cy.get('h3.pull-left').should('contain', 'Dashboard'); 
    }

    verifyThatOtherSectionAreNotVisible() {
        cy.get('nav[class="nav-sidebar"]>ul>li>a').should('contain', 'Dashboard').should('contain','Calendar').should('contain','Contacts').
        should('contain','Companies').should('contain','Segments').should('contain','Components').should('contain','Channels').should('contain','Collapse Menu');
    }

}
const user = new Users();
module.exports = user;
