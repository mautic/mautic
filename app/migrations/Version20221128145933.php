<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Form\Type\ConfigType;

final class Version20221128145933 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->container->get('mautic.helper.integration');
        $integration       = $integrationHelper->getIntegrationObject('Twilio');
        $settings          = $integration->getIntegrationSettings()->getFeatureSettings();
        if (empty($settings['disable_trackable_urls'])) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $confFile = \Mautic\CoreBundle\Loader\ParameterLoader::getLocalConfigFile(__DIR__.'/../');

        if (!file_exists($confFile)) {
            return;
        }

        require $confFile;

        $parameters[ConfigType::SMS_DISABLE_TRACKABLE_URLS] = 1;
        // Write updated config to local.php
        $result = file_put_contents($confFile, "<?php\n".'$parameters = '.var_export($parameters, true).';');

        if (false === $result) {
            throw new \Exception(sprintf("Couldn't update configuration file with enabled %s", ConfigType::SMS_DISABLE_TRACKABLE_URLS));
        }
    }
}
