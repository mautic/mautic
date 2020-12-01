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
        'mautic.transport.amazon'       => 'mautic.email.config.mailer_transport.amazon',
        'mautic.transport.amazon_api'   => 'mautic.email.config.mailer_transport.amazon_api',
        'mautic.transport.elasticemail' => 'mautic.email.config.mailer_transport.elasticemail',
        'gmail'                         => 'mautic.email.config.mailer_transport.gmail',
        'mautic.transport.mandrill'     => 'mautic.email.config.mailer_transport.mandrill',
        'mautic.transport.mailjet'      => 'mautic.email.config.mailer_transport.mailjet',
        'smtp'                          => 'mautic.email.config.mailer_transport.smtp',
        'mautic.transport.postmark'     => 'mautic.email.config.mailer_transport.postmark',
        'mautic.transport.sendgrid'     => 'mautic.email.config.mailer_transport.sendgrid',
        'mautic.transport.pepipost'     => 'mautic.email.config.mailer_transport.pepipost',
        'mautic.transport.sendgrid_api' => 'mautic.email.config.mailer_transport.sendgrid_api',
        'sendmail'                      => 'mautic.email.config.mailer_transport.sendmail',
        'mautic.transport.sparkpost'    => 'mautic.email.config.mailer_transport.sparkpost',
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
        'mautic.transport.amazon',
    ];

    /**
     * @var array
     */
    private $showUser = [
        'mautic.transport.mailjet',
        'mautic.transport.sendgrid',
        'mautic.transport.pepipost',
        'mautic.transport.elasticemail',
        'mautic.transport.amazon',
        'mautic.transport.amazon_api',
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
        'mautic.transport.amazon',
        'mautic.transport.amazon_api',
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
        'mautic.transport.amazon',
        'mautic.transport.amazon_api',
    ];

    /**
     * @param $serviceId
     * @param $translatableAlias
     * @param $showHost
     * @param $showPort
     * @param $showUser
     * @param $showPassword
     * @param $showApiKey
     */
    public function addTransport($serviceId, $translatableAlias, $showHost, $showPort, $showUser, $showPassword, $showApiKey)
    {
        $this->transportTypes[$serviceId] = $translatableAlias;

        if ($showHost) {
            $this->showHost[] = $serviceId;
        }

        if ($showPort) {
            $this->showPort[] = $serviceId;
        }

        if ($showUser) {
            $this->showUser[] = $serviceId;
        }

        if ($showPassword) {
            $this->showPassword[] = $serviceId;
        }

        if ($showApiKey) {
            $this->showApiKey[] = $serviceId;
        }
    }

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
    public function getServiceDoNotNeedAmazonRegion()
    {
        $tempTransports     = $this->transportTypes;

        $transports               = array_keys($tempTransports);
        $doNotRequireAmazonRegion = array_diff($transports, $this->showAmazonRegion);

        return $this->getString($doNotRequireAmazonRegion);
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
