<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use GuzzleHttp;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class InesCRMApi extends CrmApi
{
    private $client;

    public function __construct(CrmAbstractIntegration $integration) {
        parent::__construct($integration);

        $this->client = new GuzzleHttp\Client();
    }

    public function createLead($mappedData) {
        $this->client->request('POST', 'http://localhost:4567/push_lead', [
            'form_params' => $mappedData
        ]);
    }
}
