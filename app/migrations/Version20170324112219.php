<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\SmsBundle\Integration\TwilioIntegration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170324112219 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $row = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->prefix.'plugin_integration_settings')
            ->where('name = "Twilio"')
            ->execute()
            ->fetch()
        ;

        if ($row !== false) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->suppressNoSQLStatementError();

        $integrationRepo      = $this->container->get('doctrine.orm.entity_manager')->getRepository(Integration::class);
        $coreParametersHelper = $this->container->get('mautic.helper.core_parameters');
        $twilioKeySettings    = [
            'username' => $coreParametersHelper->getParameter('sms_username'),
            'password' => $coreParametersHelper->getParameter('sms_password'),
        ];
        $twilioFeatureSettings = [
            'sending_phone_number' => $coreParametersHelper->getParameter('sms_sending_phone_number'),
            'frequency_number'     => $coreParametersHelper->getParameter('sms_frequency_number'),
            'frequency_time'       => $coreParametersHelper->getParameter('sms_frequency_time'),
        ];
        $oneSignalKeySettings = [
            'app_id'        => $coreParametersHelper->getParameter('notification_app_id'),
            'rest_api_key'  => $coreParametersHelper->getParameter('notification_rest_api_key'),
            'safari_web_id' => $coreParametersHelper->getParameter('notification_safari_web_id'),
            'gcm_sender_id' => $coreParametersHelper->getParameter('gcm_sender_id'),
        ];
        $osFeatureSettings = [
            'features' => [],
        ];

        // Ensure an empty string doesn't get persisted, as a numeric or null is required.
        if (empty($twilioFeatureSettings['frequency_number'])) {
            $twilioFeatureSettings['frequency_number'] = null;
        }

        if ($coreParametersHelper->getParameter('notification_landing_page_enabled')) {
            $osFeatureSettings['features'][] = 'landing_page_enabled';
        }

        if ($coreParametersHelper->getParameter('welcomenotification_enabled')) {
            $osFeatureSettings['features'][] = 'welcome_notification_enabled';
        }

        if (!empty($twilioKeySettings['username'])) {
            $twilioIntegration = new Integration();
            $twilioIntegration->setName('Twilio');
            $twilioIntegration->setIsPublished($coreParametersHelper->getParameter('sms_enabled'));
            $twilioIntegration->setApiKeys([]);
            $twilioIntegration->setFeatureSettings($twilioFeatureSettings);
            $twilioIntegration->setSupportedFeatures([]);

            $twilInt = new TwilioIntegration($this->container->get('mautic.factory'));
            $twilInt->encryptAndSetApiKeys($twilioKeySettings, $twilioIntegration);

            $integrationRepo->saveEntities([$twilioIntegration]);
        }

        if (!empty($oneSignalKeySettings['rest_api_key'])) {
            $oneSignalIntegration = new Integration();
            $oneSignalIntegration->setName('OneSignal');
            $oneSignalIntegration->setIsPublished($coreParametersHelper->getParameter('notification_enabled'));
            $oneSignalIntegration->setApiKeys([]);
            $oneSignalIntegration->setFeatureSettings($osFeatureSettings);
            $oneSignalIntegration->setSupportedFeatures([]);

            $osInt = new OneSignalIntegration($this->container->get('mautic.factory'));
            $osInt->encryptAndSetApiKeys($oneSignalKeySettings, $oneSignalIntegration);

            $integrationRepo->saveEntities([$oneSignalIntegration]);
        }
    }
}
