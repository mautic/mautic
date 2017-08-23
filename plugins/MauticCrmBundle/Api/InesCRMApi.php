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

    public function createLead($mappedData, $companyName) {
        // Get INES datas template for WS
        $data = $this->getClientWithContactsTemplate();

        $data['client']['CompanyName'] = $companyName;

        foreach($mappedData as $k => $v) {
            if (substr($k, 0, 12) !== 'ines_custom_') {
                $data['client']['Contacts']['ContactInfoAuto'][0][$k] = $v;
            }
        }

        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        try {
            $return = $client->AddClientWithContacts($data);
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

    private function getClientTemplate()
    {
        return [
            'Confidentiality' => 'Undefined',
            'CompanyName' => '',
            'Type' => 0, /* filled from INES config : company type */
            'Service' => '',
            'Address1' => '',
            'Address2' => '',
            'ZipCode' => '',
            'City' => '',
            'State' => '',
            'Country' => '',
            'Phone' => '',
            'Fax' => '',
            'Website' => '',
            'Comments' => '',
            'Manager' => 0,
            'SalesResponsable' => 0,
            'TechnicalResponsable' => 0,
            'CreationDate' => date('Y-m-d\TH:i:s'),
            'ModifiedDate' => date('Y-m-d\TH:i:s'),
            'Origin' => 0,
            'CustomerNumber' => 0,
            'CompanyTaxCode' => '',
            'VatTax' => 0,
            'Bank' => '',
            'BankAccount' => '',
            'PaymentMethod' => '',
            'PaymentMethodRef' => 1, /* MANDATORY AND NOT NULL, OTHERWISE ERROR */
            'Discount' => 0,
            'HeadQuarter' => 0,
            'Language' => '',
            'Activity' => '',
            'AccountingCode' => '',
            'Scoring' => '',
            'Remainder' => 0,
            'MaxRemainder' => 0,
            'Moral' => 0,
            'Folder' => 0,
            'Currency' => '',
            'BankReference' => 0,
            'TaxType' => 0,
            'VatTaxValue' => 0,
            'Creator' => 0,
            'Delivery' => 0,
            'Billing' => 0,
            'IsNew' => true,
            'AutomationRef' => 0, /* don't fill because Mautic company concept isn't managed by the plugin */
            'InternalRef' => 0
        ];
    }

    private function getContactTemplate()
    {
        return [
            'Author' => 0,
            'BusinessAddress' => '',
            'BussinesTelephone' => '',
            'City' => '',
            'Comment' => "",
            'CompanyRef' => 0,
            'Confidentiality' => 'Undefined',
            'Country' => '',
            'CreationDate' => date('Y-m-d\TH:i:s'),
            'DateOfBirth' => date('Y-m-d\TH:i:s'),
            'Fax' => '',
            'FirstName' => '',
            'Function' => '',
            'Genre' => '',
            'HomeAddress' => '',
            'HomeTelephone' => '',
            'IsNew' => true,
            'Language' => '',
            'LastName' => '',
            'MobilePhone' => '',
            'ModificationDate' => date("Y-m-d\TH:i:s"),
            'PrimaryMailAddress' => '',
            'Rang' => 'Principal',
            'SecondaryMailAddress' => '',
            'Service' => '',
            'Type' => 0,
            'State' => '',
            'ZipCode' => '',
            'Desabo' => '',
            'NPai' => '',
            'InternalRef' => 0,
            'AutomationRef' => 0,
            'Scoring' => 0
        ];
    }

    private function getClientWithContactsTemplate($nbContacts = 1)
    {
        $data = [
            'client' => $this->getClientTemplate()
        ];

        $data['client']['Contacts'] = [
            'ContactInfoAuto' => array()
        ];

        for($i = 0; $i < $nbContacts; $i += 1) {
            $data['client']['Contacts']['ContactInfoAuto'][] = $this->getContactTemplate();
        }

        return $data;
    }
}
