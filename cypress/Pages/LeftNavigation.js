"use strict";
class LeftNavigation {
    get contactsSection() {
        return cy.get("#mautic_contact_index > .nav-item-name");
    }

    get companySection() {
        return cy.get("#mautic_company_index > .nav-item-name");
    }

    get componentsSection() {

        return cy.get("#mautic_components_root");
    }

    get formsSubSection(){
        return cy.get('a[href="/s/forms"]');
    }

    get landingPagesSubSection(){
        return cy.get('#mautic_page_index');
    }

    get StagesSection() {

        return cy.get('#mautic_stage_index');
    }

    get SegmentsSection() {

        return cy.get('#mautic_segment_index > .nav-item-name');
    }

    get CampaignsSection() {
        return cy.get("#mautic_campaign_index");
    }

    get PointsSection() {
        return cy.get('#mautic_points_root')
   }

   get ChannelsSection(){
       return cy.get('#mautic_channels_root > .nav-item-name');
   }

   get reportSection(){
    return cy.get('#mautic_report_index');
    }

    get dynamicContentSection(){
        return cy.get('#mautic_dynamicContent_index');
    }

   get EmailsSubSection(){
       return cy.get('#mautic_email_index');
   }

}
const leftNavigation = new LeftNavigation();
module.exports = leftNavigation ;
