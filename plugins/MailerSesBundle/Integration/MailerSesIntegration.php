<?php

declare(strict_types=1);

namespace MauticPlugin\MailerSesBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class MailerSesIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME = 'MailerSes';

    public const DISPLAY_NAME = 'AWS SES Mailer';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/MailerSesBundle/Assets/img/aws-ses-logo.png';
    }
}
