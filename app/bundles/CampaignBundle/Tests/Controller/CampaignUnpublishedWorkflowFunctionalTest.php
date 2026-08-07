<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignUnpublishedWorkflowFunctionalTest extends AbstractCampaignTestCase
{
    public function testCreateCampaignPageShouldNotContainConformation(): void
    {
        // Check the message in the Campaign edit page
<<<<<<< HEAD
<<<<<<< HEAD
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/campaigns/new');
=======
        $crawler  = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/campaigns/new');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/campaigns/new');
>>>>>>> 222589fde5 (cs)
        $this->assertResponseIsSuccessful();

        $attributes = [
            'data-toggle',
            'data-message',
            'data-confirm-text',
            'data-confirm-callback',
            'data-cancel-text',
            'data-cancel-callback',
        ];

        $elements = $crawler->filter('form input[name*="campaign[isPublished]"]')->getIterator();

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            foreach ($attributes as $attribute) {
                $this->assertFalse($element->hasAttribute($attribute), sprintf('The "%s" attribute is present.', $attribute));
            }
        }
    }

    public function testCampaignEditPageCheckUnpublishWorkflowAttributesPresent(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $translator = self::getContainer()->get(TranslatorInterface::class);

        // Check the message in the Campaign edit page
<<<<<<< HEAD
<<<<<<< HEAD
        $crawler  = $this->client->request(Request::METHOD_GET, sprintf('/s/campaigns/edit/%d', $campaign->getId()));
=======
        $crawler  = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, sprintf('/s/campaigns/edit/%d', $campaign->getId()));
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler  = $this->client->request(Request::METHOD_GET, sprintf('/s/campaigns/edit/%d', $campaign->getId()));
>>>>>>> 222589fde5 (cs)
        $this->assertResponseIsSuccessful();

        $republishBehavior = $translator->trans('mautic.campaignconfig.campaign_republish_behavior.'.$campaign->getRepublishBehavior());

        $attributes = [
            'onchange'               => 'Mautic.showCampaignConfirmation(mQuery(this));',
            'data-toggle'            => 'confirmation',
            'data-message-publish'   => $translator->trans('mautic.campaign.form.confirmation.message.publish', ['%republishBehavior%' => $republishBehavior]),
            'data-message-unpublish' => $translator->trans('mautic.campaign.form.confirmation.message'),
            'data-confirm-text'      => $translator->trans('mautic.campaign.form.confirmation.confirm_text'),
            'data-confirm-callback'  => 'dismissConfirmation',
            'data-cancel-text'       => $translator->trans('mautic.campaign.form.confirmation.cancel_text'),
            'data-cancel-callback'   => 'setPublishedButtonToYes',
        ];

        $elements = $crawler->filter('form input[name*="campaign[isPublished]"]')->getIterator();

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            foreach ($attributes as $key => $val) {
                $this->assertStringContainsString($val, $element->getAttribute($key));
            }
        }
    }

    public function testCampaignListPageCheckUnpublishWorkflowAttributesPresent(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $translator = self::getContainer()->get(TranslatorInterface::class);

        // Check the message in the Campaign listing page
<<<<<<< HEAD
<<<<<<< HEAD
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/campaigns');
=======
        $crawler  = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/campaigns');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/campaigns');
>>>>>>> 222589fde5 (cs)
        $this->assertResponseIsSuccessful();

        $republishBehavior = $translator->trans('mautic.campaignconfig.campaign_republish_behavior.'.$campaign->getRepublishBehavior());

        $attributes = [
            'onclick'                => 'Mautic.confirmationCampaignPublishStatus(mQuery(this));',
            'data-toggle'            => 'confirmation',
            'data-confirm-callback'  => 'confirmCallbackCampaignPublishStatus',
            'data-cancel-callback'   => 'dismissConfirmation',
            'data-message-publish'   => $translator->trans('mautic.campaign.form.confirmation.message.publish', ['%republishBehavior%' => $republishBehavior]),
            'data-message-unpublish' => $translator->trans('mautic.campaign.form.confirmation.message'),
            'data-confirm-text'      => $translator->trans('mautic.campaign.form.confirmation.confirm_text'),
            'data-cancel-text'       => $translator->trans('mautic.campaign.form.confirmation.cancel_text'),
        ];

        $toggleElement = $crawler->filter('.toggle-publish-status');
        foreach ($attributes as $key => $val) {
            $this->assertStringContainsString($val, (string) $toggleElement->attr($key));
        }
    }

    public function testCampaignUnpublishToggle(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $translator = self::getContainer()->get(TranslatorInterface::class);

        $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'campaign', 'id' => $campaign->getId()]);
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();

        $attributes    = [
            'onclick'               => 'Mautic.confirmationCampaignPublishStatus(mQuery(this));',
            'data-toggle'           => 'confirmation',
            'data-confirm-callback' => 'confirmCallbackCampaignPublishStatus',
            'data-cancel-callback'  => 'dismissConfirmation',
            'data-message'          => $translator->trans('mautic.campaign.form.confirmation.message'),
            'data-confirm-text'     => $translator->trans('mautic.campaign.form.confirmation.confirm_text'),
            'data-cancel-text'      => $translator->trans('mautic.campaign.form.confirmation.cancel_text'),
        ];

        $content = $response->getContent();

        foreach ($attributes as $key => $val) {
            $this->assertStringContainsString($key, (string) $content);
            $this->assertStringContainsString($val, (string) $content);
        }
    }
}
