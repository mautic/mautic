"use strict";
class Settings {
    get settingsMenuButton() {
        return cy.get('i[class="fa fa-cog fs-16"]');
    }

    get customFieldSection() {
        return cy.get('#mautic_lead_field > .nav-item-name');
    }

    get themesSection() {

        return cy.get('#mautic_themes_index > .nav-item-name');
    }

    get apiSection(){
        return cy.get('#mautic_client_index > .nav-item-name');
    }

    get categoriesSection(){
        return cy.get('#mautic_category_index > .nav-item-name');
    }

    get configSection() {

        return cy.get('#mautic_config_index > .nav-item-name');
    }

    get usersSection() {

        return cy.get('#mautic_user_index > .nav-item-name');
    }

    get rolesSection() {
        return cy.get('a[id="mautic_role_index"]');
    }

    get webhookSection() {
    
        return cy.get('#mautic_webhook_root > .nav-item-name')
    
   }

   get customObjectsSection(){
       return cy.get('#mautic_custom_object_list > .nav-item-name');
   }

   get pluginsSection(){
       return cy.get('#mautic_plugin_root > .nav-item-name');
   }

}
const settings = new Settings();
module.exports = settings ;
