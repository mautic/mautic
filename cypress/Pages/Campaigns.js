"use strict";
class Campaigns {

    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Campaigns');
    }

    get addNewButton() {
        return cy.get('#toolbar > div.std-toolbar.btn-group > a');
    }

    get campaignName() {
        return  cy.get('#campaign_name');
    }

    get launchCampaignBuilderButton() {
        return  cy.get('#campaign_buttons_builder_toolbar');
    }

    get saveAndCloseButton(){
        return cy.get('#campaign_buttons_save_toolbar')
    }

    get applyButton(){
        return cy.get('#campaign_buttons_apply_toolbar');
    }

    get sourceSelector(){
        return     cy.get('#SourceList');
    }

    get segmentSelector(){
        return   cy.get('#campaign_leadsource_lists_chosen>div>ul>li').eq(0);
    }

    get segmentSelectorButton(){
        return cy.get('.chosen-choices');
    }

    get addSourceCamapignButton(){
        return cy.get('#CampaignEventModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy');
    }

    get addStepButtonBottom(){
        return    cy.get("#CampaignCanvas > div.jtk-endpoint.jtk-endpoint-anchor-leadsource.CampaignEvent_lists.jtk-draggable.jtk-droppable");
    }

    get actionSelector() {
        return cy.get('#ActionGroupSelector > .panel > .panel-footer > .btn');
    }

    get listOfActions(){
        return cy.get('#ActionList');
    }

    get sendEmailActionName() {
        return cy.get('#campaignevent_name');
    }

    get emailTOBeSentSelector() {
        return cy.get('#campaignevent_properties_email_chosen');
    }

    get emailSearchBox(){
        return cy.get('#campaignevent_properties_email_chosen > div > div');
    }

    get firstEmailinTheSearchList(){
       return cy.get("#campaignevent_properties_email_chosen > div > ul > li.active-result.group-option.highlighted");
    }

    get addEmailButton(){
        return cy.get('.modal-form-buttons > .btn-save');
    }
    get closeBuilderButton() {
        return cy.get('.btn-close-campaign-builder');
    }

    get publishToggleYes() {
        return    cy.get(':nth-child(3) > .form-group > .choice-wrapper > .btn-group > .btn-yes');
    }

    get closeSummaryPageButton() {
        return cy.get('.std-toolbar > [href="/s/campaigns"]');
    }

    get searchAndSelectCampaign() {
        return cy.get('#campaignTable>tbody>tr>td>div>a[href]');
    }

    get editCampaign() {
        return cy.get('div[class="std-toolbar btn-group"]>a[href*="/s/campaigns/edit"]');
    }

    get campaignWarningMessage() {
        return cy.get('div[class="alert alert-danger"]>p');
    }

    get addCampaignEvent() {
        return cy.get('#CampaignCanvas>div[class^="jtk-endpoint jtk-endpoint-anchor-bottom"]');
    }

    get selectActionButton() {
        return cy.get('button[class="btn btn-lg btn-default btn-nospin text-primary"]');
    }

    get actionsDropDown() {
        return cy.get('#ActionList_chosen');
    }

    get typeActionTextbox() {
        return cy.get('#ActionList_chosen>div>div>input');
    }

    get typeActionTextbox_selectFirstOption() {
        return cy.get('ul[class="chosen-results"]>li').get(0);
    }

    get selectDecisionButton() {
        return cy.get('div[class="hidden-xs panel-footer text-center"]>button[data-type="Decision"]');
    }

    get decisionListOption() {
        return cy.get('#DecisionList_chosen');
    }

    get decisionListOption_TextBox() {
        return cy.get('#DecisionList_chosen>div>div>input');
    }

    get decisionListOption_SelectFirstOption() {
        return cy.get('div[class="chosen-drop"]>ul>li');
    }

    get addButton() {
        return cy.get('div[class="modal-form-buttons"]>button[class*="save"]');
    }

    get decisionActionAddition() {
        return cy.get('#CampaignCanvas>div[class^="jtk-endpoint jtk-endpoint-anchor-yes"]');
    }

    get selectAction() {
        return cy.get('div[class="jtk-endpoint jtk-endpoint-anchor-yes CampaignEvent_newe9043c68e9fe267a773e25fda05ae3a86dfb8e99 jtk-draggable jtk-droppable"]');
    }

    get actionsSearchBox() {
        return cy.get('#ActionList_chosen>div>div');
    }

    get selectSearchedAction() {
        return cy.get('#ActionList_chosen>div>ul>li');
    }

    get addPointsTextbox() {
        return cy.get('#campaignevent_properties_points');
    }

    get applyChangedBuilder() {
        return cy.get('div[class="btns-builder"]>button[onClick="Mautic.saveCampaignFromBuilder();"]');
    }

    get closeChangedBuilder() {
        return cy.get('div[class="btns-builder"]>button[onClick="Mautic.closeCampaignBuilder();"]');
    }
}
const campaigns = new Campaigns();
module.exports = campaigns;