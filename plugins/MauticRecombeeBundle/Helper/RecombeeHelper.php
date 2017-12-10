<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecombeeBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Exceptions as Ex;
use Recombee\RecommApi\Requests as Reqs;

const NUM                   = 50;
const PROBABILITY_PURCHASED = 0.2;

/**
 * Class RecombeeHelper.
 */
class RecombeeHelper
{



    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var Client
     */
    private $client;

    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $integration             = $this->integrationHelper->getIntegrationObject('Recombee');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        $apiKeys    = $integration->getKeys();
        $database   = $apiKeys['database'];
        $secret_key = $apiKeys['secret_key'];

        if (!is_object($this->client)) {
            $this->client = new Client(
                $database, $secret_key
            );
        }
    }

    public function pushLead(array $lead){

        if(empty($lead['id'])){
            return 'no lead';
        }

        try {
       $ret =     $this->getClient()->send(new Reqs\SetUserValues($lead['id'], $lead, ['cascadeCreate' => true]));
            return print_r($ret, true);
        } catch (Ex\ApiException $e) {
            return $e->getMessage();
        }
    }

    public function testItemData()
    {
        try {
            $this->getClient()->send(new Reqs\AddPurchase(444, 5, ['cascadeCreate' => true]));
        } catch (Ex\ApiException $e) {
            die($e->getMessage());
        }
    }



    public function importTestData()
    {
        try {
            // Generate some random purchases of items by users
            $purchase_requests = [];
            for ($i = 0; $i < NUM; ++$i) {
                for ($j = 0; $j < NUM; ++$j) {
                    if (mt_rand() / mt_getrandmax() < PROBABILITY_PURCHASED) {
                        $request = new Reqs\AddPurchase(
                            "{$i}", "{$j}",
                            ['cascadeCreate' => true] // Use cascadeCreate to create the
                        // yet non-existing users and items
                        );
                        array_push($purchase_requests, $request);
                    }
                }
            }
            echo "Send purchases\n";
            $res = $this->getClient()->send(
                new Reqs\Batch($purchase_requests)
            ); //Use Batch for faster processing of larger data

            // Get 5 recommendations for user 'user-25'
            $recommended = $this->getClient()->send(new Reqs\UserBasedRecommendation('25', 5));

            echo 'Recommended items: '.implode(',', $recommended)."\n";
        } catch (Ex\ApiException $e) {
            die($e->getMessage());
        }
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}
