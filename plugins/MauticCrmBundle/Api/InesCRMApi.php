<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use GuzzleHttp; // FIXME: to remove along with mock requests
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use Mautic\PluginBundle\Exception\ApiErrorException;

class InesCRMApi extends CrmApi
{
    private $translator;

    private $rootUrl = 'https://webservices.inescrm.com';

    // FIXME: to remove along with mock requests
    private $client;

    private $loginClient;

    private $automationSyncClient;

    public function __construct(CrmAbstractIntegration $integration) {
        parent::__construct($integration);
        $this->translator = $integration->getTranslator();

        // FIXME: to remove along with mock requests
        $this->client = new GuzzleHttp\Client();

        $this->loginClient = $this->makeClient('/wslogin/login.asmx');
        $this->automationSyncClient = $this->makeClient('/ws/WSAutomationSync.asmx');
    }

    private function makeClient($path) {
        return new \SoapClient($this->rootUrl . $path . '?wsdl');
    }

    private function getSessionId() {
        // TODO: cache session ID

        $keys = $this->integration->getDecryptedApiKeys();

        try {
            $response = $this->loginClient->authenticationWs([
                'request' => $keys,
            ]);
        } catch (\SoapFault $e) {
            throw new ApiErrorException($this->translator->trans('mautic.ines_crm.form.invalid_identifiers'));
        }

        return $response->authenticationWsResult->idSession;
    }

    private function setAuthHeaders($client) {
        $sessionId = $this->getSessionId();

        $headers = new \SoapHeader('http://webservice.ines.fr', 'SessionID', ['ID' => $sessionId]);
        $client->__setSoapHeaders($headers);
    }

    public function createLead($mappedData) {
        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        try {
            $return = $client->AddClientWithContacts($mappedData);
            print_r($return);die();
        } catch (\Exception $e) {
            print_r($e);die();
        }
    }

    public function createCompany($mappedData) {
        $this->client->request('POST', 'http://localhost:4567/push_company', [
            'form_params' => $mappedData
        ]);
    }

    public function getCustomFields() {
        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        return $client->GetSyncInfo();
    }
}
