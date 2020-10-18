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

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var Connection
     */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->container->get('doctrine.dbal.default_connection');

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    public function testCancelScheduledCampaignEventAction()
    {
        // Cancel the scheduled event campaign action.
        $payload = [
            'action'         => 'campaign:cancelScheduledCampaignEvent',
            'eventId'        => 1,
            'contactId'      => 1,
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // TODO FINISH TEST
    }
}
