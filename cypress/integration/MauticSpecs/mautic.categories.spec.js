/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const settings = require("../../Pages/Settings");
const catagory = require("../../Pages/Categories");
const search=require("../../Pages/Search");

var emailCategory = "EmailCategory1"

context("Verify that user is able to create and delete catagories", () => {
  it("Add new email catagory", () => {
    settings.settingsMenuButton.click({force: true})
    catagory.categoryLink.click({force: true})
    catagory.waitforPageLoad()
    catagory.createNewCategory.click({force: true})
    catagory.waitTillCategoryPopUpLaunch()
    catagory.categoryType.click()
    catagory.searchCategoryType.type('Email')
    catagory.selectTheFirstSearch.click()
    catagory.titleCategory.type(emailCategory)
    catagory.saveAndCloseChanges.click()
    catagory.waiitTillCategoryCreation.should('be.visible').should('contain', emailCategory);
  });

  it("Search and delete newly email catagory", () => {
    settings.settingsMenuButton.click();
    catagory.categoryLink.click({force: true})
    catagory.waitforPageLoad()
    cy.visit('s/categories?search='+ emailCategory);
    search.selectTheParentCheckBox.click();
    catagory.selectParentButtonForDelete.click();
    search.deleteAllSelected.click();
    search.confirmDeleteButton.click();
    catagory.checkNoResultFoundMessage.should('contain','No Results Found');
  });

  });



