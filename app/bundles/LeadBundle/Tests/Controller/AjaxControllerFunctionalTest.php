<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Tests\DataFixtures\Orm\CampaignData;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testToggleLeadCampaignAction()
    {
        $this->installDatabaseFixtures([LeadFieldData::class, CampaignData::class, LoadLeadData::class]);
        $payload = [
            'action'         => 'lead:toggleLeadCampaign',
            'leadId'         => 1,
            'campaignId'     => 1,
            'campaignAction' => 'add',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        dump($response);

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertTrue(isset($response['success']), 'The response does not contain the `success` param.');
        $this->assertSame(1, $response['success']);
    }
}
