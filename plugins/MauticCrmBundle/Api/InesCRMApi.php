<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use Mautic\PluginBundle\Exception\ApiErrorException;

class InesCRMApi extends CrmApi
{
    const ROOT_URL = 'https://webservices.inescrm.com';

    const LOGIN_WS_PATH = '/wslogin/login.asmx';

    const CONTACT_MANAGER_WS_PATH = '/ws/wsicm.asmx';

    const CUSTOM_FIELD_WS_PATH = '/ws/wscf.asmx';

    const AUTOMATION_SYNC_WS_PATH = '/ws/WSAutomationSync.asmx';

    private $translator;

    private $cachedAuthHeader = null;

    private $loginClient = null;

    private $contactManagerClient = null;

    private $customFieldClient = null;

    private $automationSyncClient = null;

    public function __construct(CrmAbstractIntegration $integration) {
        parent::__construct($integration);
        $this->translator = $integration->getTranslator();

        $this->loginClient = $this->makeClient(self::LOGIN_WS_PATH);
        $this->contactManagerClient = $this->makeClient(self::CONTACT_MANAGER_WS_PATH);
        $this->customFieldClient = $this->makeClient(self::CUSTOM_FIELD_WS_PATH);
        $this->automationSyncClient = $this->makeClient(self::AUTOMATION_SYNC_WS_PATH);
    }

    public function getSyncInfo() {
        $client = $this->automationSyncClient;
        $this->includeAuthHeader($client);

        return $client->GetSyncInfo();
    }

    public function getClientCustomFields($internalRef) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        $response = $client->GetCompanyCF(['reference' => $internalRef]);
        self::cleanList($response->GetCompanyCFResult->Values->CustomField);
        return $response;
    }

    public function getContactCustomFields($internalRef) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        $response = $client->GetContactCF(['reference' => $internalRef]);
        self::cleanList($response->GetContactCFResult->Values->CustomField);
        return $response;
    }

    public function createClientCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        return $client->InsertCompanyCF($mappedData);
    }

    public function updateClientCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        return $client->UpdateCompanyCF($mappedData);
    }

    public function createContactCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        return $client->InsertContactCF($mappedData);
    }

    public function updateContactCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->includeAuthHeader($client);

        return $client->UpdateContactCF($mappedData);
    }

    public function getClient($internalRef) {
        $client = $this->contactManagerClient;
        $this->includeAuthHeader($client);

        return $client->GetClient(['reference' => $internalRef]);
    }

    public function getContact($internalRef) {
        $client = $this->contactManagerClient;
        $this->includeAuthHeader($client);

        return $client->GetContact(['reference' => $internalRef]);
    }

    public function createClientWithContacts($mappedData) {
        $client = $this->automationSyncClient;
        $this->includeAuthHeader($client);

        return $client->AddClientWithContacts($mappedData);
    }

    public function createClient($mappedData) {
        $client = $this->contactManagerClient;
        $this->includeAuthHeader($client);

        return $client->AddClient($mappedData);
    }

    public function createContact($mappedData) {
        $client = $this->automationSyncClient;
        $this->includeAuthHeader($client);

        return $client->AddContact($mappedData);
    }

    public function updateClient($inesClient) {
        $client = $this->contactManagerClient;
        $this->includeAuthHeader($client);

        return $client->UpdateClient(['client' => $inesClient]);
    }

    public function updateContact($inesContact) {
        $client = $this->contactManagerClient;
        $this->includeAuthHeader($client);

        return $client->UpdateContact(['contact' => $inesContact]);
    }

    private function makeClient($path) {
        return new \SoapClient(self::ROOT_URL . $path . '?wsdl');
    }

    private function includeAuthHeader($client) {
        if (is_null($this->cachedAuthHeader)) {
            $sessionId = $this->getSessionId();
            $this->cachedAuthHeader = new \SoapHeader('http://webservice.ines.fr', 'SessionID', ['ID' => $sessionId]);
        }

        $client->__setSoapHeaders($this->cachedAuthHeader);
    }

    private function getSessionId() {
        $keys = $this->integration->getDecryptedApiKeys();
        $failed = false;

        try {
            $response = $this->loginClient->authenticationWs([
                'request' => $keys,
            ]);

            if ($response->authenticationWsResult->codeReturn === 'failed') {
                $failed = true;
            }
        } catch (\SoapFault $e) {
            $failed = true;
        }

        if ($failed) {
            throw new ApiErrorException($this->translator->trans('mautic.ines_crm.form.invalid_identifiers'));
        }

        return $response->authenticationWsResult->idSession;
    }

    private static function cleanList(&$dirtyList) {
        if (is_null($dirtyList)) {
            $dirtyList = [];
        } elseif (!is_array($dirtyList)) {
            $dirtyList = [$dirtyList];
        }
    }
}
