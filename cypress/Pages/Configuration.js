"use strict";
class Configuration {

    get openSettings() {
        return  cy.get('.navbar-right > :nth-child(2) > a');
    }

    get goToConfig() {
        return  cy.get('#mautic_config_index');
    }

    get clickOnConfigurationSetings() {
        return   cy.get('a[href*="config/edit"]');
    }

    get clickOnEmailSettings() {
        return   cy.get('a[href*="#emailconfig"]');
    }

    get selectFrequencyForEmail() {
        return   cy.get('#config_emailconfig_email_frequency_number');
    }

    get clickFrequencyEach() {
        return   cy.get('#config_emailconfig_email_frequency_time_chosen');
    }

    get selectFrequencyForWeek() {
        return   cy.get('ul[class="chosen-results"]>li').eq(1);
    }

    get applyEmailSetting() {
        return   cy.get('#config_buttons_apply_toolbar');
    }

    get saveAndCloseEmailSetting() {
        return   cy.get('#config_buttons_save_toolbar');
    }

    waitforPageLoad (){
        cy.get('h3.pull-left').should('contain', 'Configuration');
    }

    waitTillSettingsGetsApplied (){
        cy.get('#config_coreconfig_webroot_chosen').should('be.visible')
    }

    waitforEmailSettingPageLoad (){
        cy.get('#config_emailconfig_email_frequency_number').should('be.visible')
    }

    waitforSettingApplied (){
        cy.get('.alert-growl').should('contain', 'Configuration successfully updated');
    }

    waitTillUserRedirectedToDashboard (){
        cy.get('h3.pull-left').should('contain', 'Dashboard');
    }

    get closeAlert() {
        return   cy.get('.alert-growl>button');
    }
  
}
const configuration = new Configuration();
module.exports = configuration;