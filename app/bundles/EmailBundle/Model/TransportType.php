<?php

namespace Mautic\EmailBundle\Model;

class TransportType
{
    public function getTransportTypes()
    {
        return [
            'mautic.transport.amazon'       => 'mautic.email.config.mailer_transport.amazon',
            'mautic.transport.elasticemail' => 'mautic.email.config.mailer_transport.elasticemail',
            'gmail'                         => 'mautic.email.config.mailer_transport.gmail',
            'mautic.transport.mandrill'     => 'mautic.email.config.mailer_transport.mandrill',
            'mautic.transport.mailjet'      => 'mautic.email.config.mailer_transport.mailjet',
            'smtp'                          => 'mautic.email.config.mailer_transport.smtp',
            'mail'                          => 'mautic.email.config.mailer_transport.mail',
            'mautic.transport.postmark'     => 'mautic.email.config.mailer_transport.postmark',
            'mautic.transport.sendgrid'     => 'mautic.email.config.mailer_transport.sendgrid',
            'mautic.transport.sendgrid_api' => 'mautic.email.config.mailer_transport.sendgrid_api',
            'sendmail'                      => 'mautic.email.config.mailer_transport.sendmail',
            'mautic.transport.sparkpost'    => 'mautic.email.config.mailer_transport.sparkpost',
        ];
    }

    public function getSmtpService()
    {
        return '"smtp"';
    }

    public function getAmazonService()
    {
        return '"mautic.transport.amazon"';
    }

    public function getMailjetService()
    {
        return '"mautic.transport.mailjet"';
    }

    public function getServiceRequiresLogin()
    {
        return '"mautic.transport.mandrill",
                "mautic.transport.mailjet",
                "mautic.transport.sendgrid",
                "mautic.transport.elasticemail",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "gmail"';
    }

    public function getServiceDoNotNeedLogin()
    {
        return '"mail",
                "sendmail",
                "mautic.transport.sparkpost",
                "mautic.transport.sendgrid_api"';
    }

    public function getServiceRequiresPassword()
    {
        return '"mautic.transport.elasticemail",
                "mautic.transport.sendgrid",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "mautic.transport.mailjet",
                "gmail"';
    }

    public function getServiceDoNotNeedPassword()
    {
        return '"mail",
                "sendmail",
                "mautic.transport.sparkpost",
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid_api"';
    }

    public function getServiceRequiresApiKey()
    {
        return '"mautic.transport.sparkpost",
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid_api"';
    }
}
