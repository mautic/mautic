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

use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Exceptions as Ex;
use Recombee\RecommApi\Requests as Reqs;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @var RequestStack
     */
    protected $request;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $components = ['CartAddition', 'Purchase', 'Rating', 'Bookmark', 'DetailView'];

    /**
     * @var array
     */
    private $actions = ['Add', 'Delete'];

    public function __construct(IntegrationHelper $integrationHelper, RequestStack $requestStack, LeadModel $leadModel)
    {
        $this->integrationHelper = $integrationHelper;
        $this->request           = $requestStack;
        $this->leadModel         = $leadModel;

        $integration = $this->integrationHelper->getIntegrationObject('Recombee');
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

    public function setLeadData($data)
    {
        $key = reset(array_keys($data));

        switch ($key) {
            case 'mautic.lead_post_save_new':
            case 'mautic.lead_post_save_update':
            case 'mautic.lead_points_change':
                $action = 'Add';
                $this->request->setMethod('POST');
                break;
            case 'mautic.lead_post_delete':
                $action = 'Delete';
                $this->request->setMethod('DELETE');
                break;
        }
    }

    /**
     * @param $component
     * @param $leadId
     * @param $action
     * @param $idemId
     * @param bool  $cascadeCreate
     * @param array $params
     *
     * @return bool
     */
    public function setLeadAction($component, $leadId, $action, $idemId, $params = [])
    {
        if (!in_array($component, $this->components) || !in_array($action, $this->actions)) {
            return false;
        }

        $lead = $this->leadModel->getEntity($leadId);
        if (!$lead instanceof Lead || !$lead->getId()) {
            return false;
        }

        // change method from POST to DELETE
        if ($action == 'add') {
            $this->request->setMethod('POST');
        } elseif ($action == 'delete') {
            $this->request->setMethod('DELETE');
        } elseif ($action == 'merge') {
            $this->request->setMethod('PUT');
        }

        try {
            $class = 'Recombee\\RecommApi\\Requests\\'.$action.$component;
            $this->recombeeHelper->getClient()->send(new $class($leadId, $idemId, $params));

            return true;
        } catch (Ex\ApiException $e) {
        }

        return false;
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
