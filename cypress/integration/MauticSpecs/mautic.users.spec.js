/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const user = require("../../Pages/Users");
const search=require("../../Pages/Search");
const roles = require("../../Pages/Roles");

var username = "StandardAccount"
var userEmail = "user@mailtest.mautic.com"
var password = "Standard@12345"
var readRole = "Standard"
var firstName = "Test"
var lastName = "User"

context("Verify that user is able to attach role to the user and logged in successfully as per role privilege", () => {

  it("Add new role with read contacts access", () => {
    cy.visit("s/roles");
    roles.waitForPageLoad();
    roles.addNewRoleButton.click();
    roles.roleNameTextBox.click();
    roles.roleNameTextBox.type(readRole);
    roles.permissionTab.click();
    roles.apiPermissionTab.click();
    roles.apiAccessGrantedCheckBox.click();
    cy.wait(1000);
    roles.contactPermissionTab.click();
    roles.contacts_Access_Full.click();
    roles.contacts_Segments_ViewOthers.click()
    roles.contacts_Segments_EditOthers.click()
    roles.contacts_import_View.click()
    roles.contacts_import_Edit.click()
    roles.saveAndCloseTab.click();
    roles.waitforRoleCreation();
  });

  it("Create new user with role attached above", () => {
    cy.visit("s/users");
    user.waitForPageLoad()
    user.clickOnCreateNewUserTab.click()
    user.waitTillNewUserCreationPageGetsLoaded()
    user.User_firstName.type(firstName)
    user.User_lastName.type(lastName)
    user.User_Role.click()
    user.searchForRole.type(readRole)
    user.selectSearchedRole.click()
    user.userName.type(username)
    user.userEmail.type(userEmail)
    user.userPassword.type(password)
    user.userConfirmPassword.type(password)
    user.saveUser.click()
    user.waitTillUserGetsCreated()
  });

  it("logout and login with created user", () => {
    user.clickOnUserName.click()
    user.clickOnLogoutButton.click()
    user.waitTillLoginPageDisplayed()
    cy.login(userEmail, password);
    user.waitTillDashboardGetsLoaded()
    user.verifyThatOtherSectionAreNotVisible()
  });

  it("Login with Admin credentials", () => {
    user.clickOnUserName.click()
    user.clickOnLogoutButton.click()
    user.waitTillLoginPageDisplayed()
    cy.login(Cypress.env("userName"), Cypress.env("password"));
    user.waitTillDashboardGetsLoaded()
  });

  it("Delete the created User", () => {
    cy.visit("s/users");
    user.waitForPageLoad();
    cy.visit('/s/users?search=' + lastName)
    user.selectParentCheckBox.click()
    user.selectParentDropdown.click()
    user.deleteUsersBatch.click()
    user.confirmDeleteButton.click()
    cy.get('.alert-growl').should('contain', '1 users have been deleted!');
  });

  it("Search and delete newly added role", () => {
    cy.visit("s/roles");
    roles.waitForPageLoad();
    cy.visit('/s/roles?search='+ readRole);
    search.selectCheckBoxForFirstItem.click();
    search.OptionsDropdownForFirstItem.click();
    search.deleteButtonForFirstItem.click();
    search.confirmDeleteButton.click();
  });

  });


