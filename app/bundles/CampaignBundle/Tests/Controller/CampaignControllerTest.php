<?php

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerTest extends MauticMysqlTestCase
{
    /**
     * Index should return status code 200.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/campaigns');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/campaigns?search=has%3Aresults&tmpl=list');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Get campaign's create page.
     */
    public function testNewActionCampaign(): void
    {
        $this->client->request('GET', '/s/campaigns/new/');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Test cancelling new campaign does not give a 500 error.
     *
     * @see https://github.com/mautic/mautic/issues/11181
     */
    public function testNewActionCampaignCancel(): void
    {
        $crawler = $this->client->request('GET', '/s/campaigns/new/');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="campaign"]')->selectButton('campaign_buttons_cancel')->form();
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
    }
}
