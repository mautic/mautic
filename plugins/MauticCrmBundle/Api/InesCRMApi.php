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
    }

    public function getSyncInfo() {
        $client = $this->getAutomationSyncClient();

        $response = $client->GetSyncInfo();
        self::cleanList($response->GetSyncInfoResult->CompanyCustomFields->CustomFieldToAuto);
        self::cleanList($response->GetSyncInfoResult->ContactCustomFields->CustomFieldToAuto);
        return $response;
    }

    public function getClientCustomFields($internalRef) {
        $client = $this->getCustomFieldClient();

        $response = $client->GetCompanyCF(['reference' => $internalRef]);
        self::cleanList($response->GetCompanyCFResult->Values->CustomField);
        self::cleanList($response->GetCompanyCFResult->Definitions->CustomFieldDefinition);
        self::cleanList($response->GetCompanyCFResult->Groups->CustomFieldGroup);
        return $response;
    }

    public function getContactCustomFields($internalRef) {
        $client = $this->getCustomFieldClient();

        $response = $client->GetContactCF(['reference' => $internalRef]);
        self::cleanList($response->GetContactCFResult->Values->CustomField);
        self::cleanList($response->GetContactCFResult->Definitions->CustomFieldDefinition);
        self::cleanList($response->GetContactCFResult->Groups->CustomFieldGroup);
        return $response;
    }

    public function createClientCustomField($mappedData) {
        $client = $this->getCustomFieldClient();

        return $client->InsertCompanyCF($mappedData);
    }

    public function updateClientCustomField($mappedData) {
        $client = $this->getCustomFieldClient();

        return $client->UpdateCompanyCF($mappedData);
    }

    public function createContactCustomField($mappedData) {
        $client = $this->getCustomFieldClient();

        return $client->InsertContactCF($mappedData);
    }

    public function updateContactCustomField($mappedData) {
        $client = $this->getCustomFieldClient();

        return $client->UpdateContactCF($mappedData);
    }

    public function getClient($internalRef) {
        $client = $this->getContactManagerClient();

        return $client->GetClient(['reference' => $internalRef]);
    }

    public function getContact($internalRef) {
        $client = $this->getContactManagerClient();

        return $client->GetContact(['reference' => $internalRef]);
    }

    public function createClientWithContacts($mappedData) {
        $client = $this->getAutomationSyncClient();

        return $client->AddClientWithContacts($mappedData);
    }

    public function createClient($mappedData) {
        $client = $this->getContactManagerClient();

        return $client->AddClient($mappedData);
    }

    public function createContact($mappedData) {
        $client = $this->getAutomationSyncClient();

        return $client->AddContact($mappedData);
    }

    public function updateClient($inesClient) {
        $client = $this->getContactManagerClient();

        return $client->UpdateClient(['client' => $inesClient]);
    }

    public function updateContact($inesContact) {
        $client = $this->getAutomationSyncClient();

        return $client->UpdateContact(['contact' => $inesContact]);
    }

    private function getLoginClient() {
        if (is_null($this->loginClient)) {
            $this->loginClient = self::makeClient(self::LOGIN_WS_PATH);
        }

        return $this->loginClient;
    }

    private function getContactManagerClient() {
        if (is_null($this->contactManagerClient)) {
            $this->contactManagerClient = self::makeClient(self::CONTACT_MANAGER_WS_PATH);
            $this->includeAuthHeader($this->contactManagerClient);
        }

        return $this->contactManagerClient;
    }

    private function getCustomFieldClient() {
        if (is_null($this->customFieldClient)) {
            $this->customFieldClient = self::makeClient(self::CUSTOM_FIELD_WS_PATH);
            $this->includeAuthHeader($this->customFieldClient);
        }

        return $this->customFieldClient;
    }

    private function getAutomationSyncClient() {
        if (is_null($this->automationSyncClient)) {
            $this->automationSyncClient = self::makeClient(self::AUTOMATION_SYNC_WS_PATH);
            $this->includeAuthHeader($this->automationSyncClient);
        }

        return $this->automationSyncClient;
    }

    private static function makeClient($path) {
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
            $response = $this->getLoginClient()->authenticationWs([
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
        if (!isset($dirtyList)) {
            $dirtyList = [];
        } elseif (!is_array($dirtyList)) {
            $dirtyList = [$dirtyList];
        }
    }
}
