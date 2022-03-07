<?php

namespace Mautic\EmailBundle\Model;

class TransportType
{
    const TRANSPORT_ALIAS = 'transport_alias';

    const FIELD_HOST     = 'field_host';
    const FIELD_PORT     = 'field_port';
    const FIELD_USER     = 'field_user';
    const FIELD_PASSWORD = 'field_password';
    const FIELD_API_KEY  = 'field_api_key';

    /**
     * @var array
     */
    private $transportTypes = [
        'ses+api'  => 'mautic.email.config.mailer_transport.amazon_api',
        'smtp'     => 'mautic.email.config.mailer_transport.smtp',
    ];

    /**
     * @var array
     */
    private $showHost = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showPort = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showUser = [
        'mautic.transport.mailjet',
        'mautic.transport.sendgrid',
        'mautic.transport.pepipost',
        'mautic.transport.elasticemail',
        'ses+api',
        'mautic.transport.postmark',
        'gmail',
        // smtp is left out on purpose as the auth_mode will manage displaying this field
    ];

    /**
     * @var array
     */
    private $showPassword = [
        'mautic.transport.mailjet',
        'mautic.transport.sendgrid',
        'mautic.transport.pepipost',
        'mautic.transport.elasticemail',
        'ses+smtp',
        'ses+api',
        'mautic.transport.postmark',
        'gmail',
        // smtp is left out on purpose as the auth_mode will manage displaying this field
    ];

    /**
     * @var array
     */
    private $showApiKey = [
        'mautic.transport.sparkpost',
        'mautic.transport.mandrill',
        'mautic.transport.sendgrid_api',
    ];

    /**
     * @var array
     */
    private $showAmazonRegion = [
        'ses+api',
    ];

    /**
     * @return array
     */
    public function getTransportTypes()
    {
        return $this->transportTypes;
    }

    /**
     * @return string
     */
    public function getServiceRequiresHost()
    {
        return $this->getString($this->showHost);
    }

    /**
     * @return string
     */
    public function getServiceRequiresPort()
    {
        return $this->getString($this->showPort);
    }

    /**
     * @return string
     */
    public function getServiceRequiresUser()
    {
        return $this->getString($this->showUser);
    }

    /**
     * @return string
     */
    public function getServiceDoNotNeedUser()
    {
        // The auth_mode data-show-on will handle smtp
        $tempTransports = $this->transportTypes;
        unset($tempTransports['smtp']);

        $transports       = array_keys($tempTransports);
        $doNotRequireUser = array_diff($transports, $this->showUser);

        return $this->getString($doNotRequireUser);
    }

    public function getServiceDoNotNeedPassword()
    {
        // The auth_mode data-show-on will handle smtp
        $tempTransports = $this->transportTypes;
        unset($tempTransports['smtp']);

        $transports       = array_keys($tempTransports);
        $doNotRequireUser = array_diff($transports, $this->showPassword);

        return $this->getString($doNotRequireUser);
    }

    /**
     * @return string
     */
    public function getServiceRequiresPassword()
    {
        return $this->getString($this->showPassword);
    }

    /**
     * @return string
     */
    public function getServiceRequiresApiKey()
    {
        return $this->getString($this->showApiKey);
    }

    /**
     * @return string
     */
    public function getSmtpService()
    {
        return '"smtp"';
    }

    /**
     * @return string
     */
    public function getAmazonService()
    {
        return $this->getString($this->showAmazonRegion);
    }

    /**
     * @return string
     */
    public function getMailjetService()
    {
        return '"mautic.transport.mailjet"';
    }

    /**
     * @return string
     */
    private function getString(array $services)
    {
        return '"'.implode('","', $services).'"';
    }
}
