/*jslint es6 */
/// <reference types="Cypress" />
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const leftNavigation = require("../../Pages/LeftNavigation");
const campaigns = require("../../Pages/Campaigns");
const search = require("../../Pages/Search");

var TestCampaign = "TestCampaign";

context("Campaign", () => {
 
  it("Add new Campaign", () => {
    leftNavigation.CampaignsSection.click();
    campaigns.waitforPageLoad();
    cy.wait(1000);
    search.searchBox.should('exist');
    campaigns.addNewButton.click();
    campaigns.campaignName.type(TestCampaign);
    campaigns.launchCampaignBuilderButton.click({ force: true });
    campaigns.sourceSelector.select("Contact segments", { force: true });
    campaigns.segmentSelectorButton.click();
    campaigns.segmentSelector.click();
    campaigns.addSourceCamapignButton.click({ force: true });
    campaigns.addStepButtonBottom.click({ force: true });
    campaigns.actionSelector.click();
    campaigns.listOfActions.select("Send email", { force: true });
    campaigns.sendEmailActionName.type("Test Campaign Email");
    campaigns.emailTOBeSentSelector.click();
    campaigns.emailSearchBox.type("Test");
    campaigns.firstEmailinTheSearchList.should("be.visible");
    campaigns.firstEmailinTheSearchList.click();
    campaigns.addEmailButton.click();
    campaigns.closeBuilderButton.click();
    campaigns.publishToggleYes.click();
    campaigns.saveAndCloseButton.click();
    campaigns.closeSummaryPageButton.click();
    search.searchBox.clear();
    search.searchBox.type(TestCampaign);
    search.selectCheckBoxForFirstItem.should('exist');
  });

  it("Edit the newly added Campaign", () => {
    leftNavigation.CampaignsSection.click();
    campaigns.waitforPageLoad();
    cy.visit('/s/campaigns?search='+ TestCampaign)
    campaigns.searchAndSelectCampaign.eq(0).click().contains(TestCampaign);
    campaigns.editCampaign.click();
    campaigns.launchCampaignBuilderButton.click();
    cy.wait(1000);
    campaigns.addCampaignEvent.should("be.visible");
    campaigns.addCampaignEvent.click();
    campaigns.selectDecisionButton.click();
    campaigns.decisionListOption.click();
    campaigns.decisionListOption_TextBox.type("opens email");
    campaigns.decisionListOption_SelectFirstOption.click();
    cy.wait(1000);
    campaigns.sendEmailActionName.should("be.visible");
    campaigns.sendEmailActionName.type("Opens Email");
    campaigns.addButton.click();
    cy.wait(2000);
    campaigns.decisionActionAddition.click();
    campaigns.selectActionButton.click();
    campaigns.actionsSearchBox.type("Adjust contact points");
    campaigns.selectSearchedAction.click();
    cy.wait(2000);
    campaigns.sendEmailActionName.should("be.visible");
    campaigns.sendEmailActionName.type("Add Points for opening email");
    campaigns.addPointsTextbox.clear().type("5");
    campaigns.addButton.click();
    campaigns.applyChangedBuilder.click();
    cy.wait(3000);
    campaigns.closeChangedBuilder.should("be.visible");
    campaigns.closeChangedBuilder.click();
    campaigns.saveAndCloseButton.click();
    campaigns.closeSummaryPageButton.click();
  });

});
